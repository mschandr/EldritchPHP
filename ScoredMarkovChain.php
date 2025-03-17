<?php

class PersistentMarkovChain {
    private $chain = [];
    private $scoresFile = "scores.json";
    private $modelFile = "markov_model.json";

    public function __construct() {
        $this->loadModel();
    }

    public function train($text) {
        $words = explode(' ', strtolower($text));

        for ($i = 0; $i < count($words) - 2; $i++) {
            $pair = $words[$i] . ' ' . $words[$i + 1];
            $nextWord = $words[$i + 2];

            if (!isset($this->chain[$pair])) {
                $this->chain[$pair] = [];
            }

            if (!isset($this->chain[$pair][$nextWord])) {
                $this->chain[$pair][$nextWord] = 1; // Default weight
            } else {
                $this->chain[$pair][$nextWord]++; // Increase occurrence count
            }
        }
        $this->saveModel(); // Save updated model
    }

    public function generate($maxWords = 15) {
        $validStartPairs = array_keys($this->chain);
        $currentPair = $validStartPairs[array_rand($validStartPairs)];
        $sentence = explode(' ', $currentPair);

        for ($i = 0; $i < $maxWords; $i++) {
            if (!isset($this->chain[$currentPair])) break;

            $nextWord = $this->selectNextWord($this->chain[$currentPair]);
            $sentence[] = $nextWord;
            $currentPair = $sentence[count($sentence) - 2] . ' ' . $sentence[count($sentence) - 1];

            if ($this->shouldStop($sentence)) {
                break;
            }
        }

        $this->finalizeSentence($sentence);
        return ucfirst(implode(' ', $sentence));
    }

    private function selectNextWord($options) {
        $totalWeight = array_sum($options);
        $rand = mt_rand(1, $totalWeight);
        $cumulative = 0;

        foreach ($options as $word => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $word;
            }
        }

        return array_key_first($options); // Fallback if selection fails
    }

    private function shouldStop($sentence) {
        $minWords = 6;
        $stopProbability = 0.25;
        $strongWords = ['dead', 'watching', 'name', 'forgotten', 'end', 'truth', 'return', 'empty', 'door', 'darkness'];

        if (count($sentence) < $minWords) {
            return false;
        }

        $lastWord = end($sentence);
        if (preg_match('/[.!?]+$/', $lastWord) && (in_array(strtolower($lastWord), $strongWords) || mt_rand(1, 100) / 100.0 < $stopProbability)) {
            return true;
        }

        return false;
    }

    private function finalizeSentence(&$sentence) {
        $lastWord = end($sentence);
        if (!preg_match('/[.!?]+$/', $lastWord)) {
            $sentence[] = '.';
        }
    }

    public function scoreProphecy($prophecy) {
        echo "📜 Your Eldritch Prophecy: $prophecy\n";
        echo "Rate this prophecy (1-5): ";
        $score = trim(fgets(STDIN));

        if (!in_array($score, ['1', '2', '3', '4', '5'])) {
            echo "Invalid score. Must be between 1-5.\n";
            return;
        }

        $this->saveScore($prophecy, (int)$score);
    }

    private function saveScore($prophecy, $score) {
        $scores = [];

        if (file_exists($this->scoresFile)) {
            $scores = json_decode(file_get_contents($this->scoresFile), true);
        }

        $scores[] = ["prophecy" => $prophecy, "score" => $score];
        file_put_contents($this->scoresFile, json_encode($scores, JSON_PRETTY_PRINT));

        echo "Saved score: $score\n";
    }

    public function improveModel() {
        if (!file_exists($this->scoresFile)) {
            echo "No scores available. Train more first.\n";
            return;
        }

        $scores = json_decode(file_get_contents($this->scoresFile), true);

        foreach ($scores as $entry) {
            $prophecy = explode(' ', strtolower($entry["prophecy"]));
            $score = $entry["score"];

            for ($i = 0; $i < count($prophecy) - 2; $i++) {
                $pair = $prophecy[$i] . ' ' . $prophecy[$i + 1];
                $nextWord = $prophecy[$i + 2];

                if (isset($this->chain[$pair][$nextWord])) {
                    $this->chain[$pair][$nextWord] += ($score - 3); // Adjust weight based on score
                }
            }
        }
        $this->saveModel(); // Save after updating model
        echo "Model updated based on scores.\n";
    }

    private function saveModel() {
        file_put_contents($this->modelFile, json_encode($this->chain, JSON_PRETTY_PRINT));
    }

    private function loadModel() {
        if (file_exists($this->modelFile)) {
            $this->chain = json_decode(file_get_contents($this->modelFile), true);
        }
    }

    private function cleanProphecy($text) {
        // Remove all punctuation except spaces and newlines
        $text = preg_replace("/[^\w\s]/", "", $text);

        // Remove excessive spaces and newlines
        $text = trim(preg_replace('/\s+/', ' ', $text));

        // Ensure a period at the end
        if (!preg_match('/[.!?]$/', $text)) {
            $text .= '.';
        }

        return ucfirst($text); // Capitalize first letter
    }
}

// Load training data
$prophecyText = file_get_contents(__DIR__ . '/training_data/file.txt');
$markov = new PersistentMarkovChain();
$markov->train($prophecyText);

while (true) {
    $prophecy = $markov->generate();
    $markov->scoreProphecy($prophecy);

    echo "Generate another prophecy? (y/n): ";
    $response = trim(fgets(STDIN));
    if (strtolower($response) !== 'y') {
        echo "Would you like to improve the model based on scores? (y/n): ";
        $trainResponse = trim(fgets(STDIN));
        if (strtolower($trainResponse) === 'y') {
            $markov->improveModel();
        }
        break;
    }
}

