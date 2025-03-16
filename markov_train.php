<?php

class MarkovChain {
    private $chain = [];

    public function train($text) {
        $words = explode(' ', strtolower($text));

        for ($i = 0; $i < count($words) - 2; $i++) {
            $pair = $words[$i] . ' ' . $words[$i + 1]; // Create two-word pairs
            $nextWord = $words[$i + 2];

            if (!isset($this->chain[$pair])) {
                $this->chain[$pair] = [];
            }

            if (!isset($this->chain[$pair][$nextWord])) {
                $this->chain[$pair][$nextWord] = 0;
            }

            $this->chain[$pair][$nextWord]++;
        }

        foreach ($this->chain as $pair => $nextWords) {
            $total = array_sum($nextWords);
            foreach ($nextWords as $nextWord => $count) {
                // Normalize word probabilities
                $weight = ($count / $total);
                if ($weight > 0.3) {
                    $weight = 0.3 + (0.7 * (rand(1, 10) / 10)); // Adjust probability variation
                }
                $this->chain[$pair][$nextWord] = $weight;
            }
        }
    }

    public function generate($maxWords = 15) {
        $validStartPairs = array_keys($this->chain);

        // Ensure we start with an actual phrase (not a random word)
        $currentPair = $validStartPairs[array_rand($validStartPairs)];
        $sentence = explode(' ', $currentPair);

        $forceStopAfter = 18; // Force stop if exceeding max length

        for ($i = 0; $i < $forceStopAfter; $i++) {
            if (!isset($this->chain[$currentPair])) break;

            $nextWords = $this->chain[$currentPair];
            $randomPick = mt_rand(1, 100) / 100.0;
            $cumulative = 0;

            foreach ($nextWords as $word => $probability) {
                $cumulative += $probability;
                if ($randomPick <= $cumulative) {
                    $sentence[] = $word;
                    $currentPair = $sentence[count($sentence) - 2] . ' ' . $sentence[count($sentence) - 1];
                    break;
                }
            }

            // Check if we should stop generating
            if ($this->shouldStop($sentence)) {
                break;
            }
        }

        // Ensure proper sentence closure
        $this->finalizeSentence($sentence);

        return ucfirst(implode(' ', $sentence));
    }

    private function shouldStop($sentence) {
        $minWords = 6;
        $stopProbability = 0.25; // 25% chance to stop if punctuation is found

        if (count($sentence) < $minWords) {
            return false; // Continue if sentence is too short
        }

        if (preg_match('/[.!?]+$/', end($sentence)) && mt_rand(1, 100) / 100.0 < $stopProbability) {
            return true; // Stop if we hit punctuation and probability allows
        }

        return false;
    }

    private function finalizeSentence(&$sentence) {
        $lastWord = end($sentence);
        if (!preg_match('/[.!?]+$/', $lastWord)) {
            $sentence[] = '.'; // Ensure proper sentence ending
        }
    }
}

// Load prophecy training data
$prophecyText = file_get_contents(__DIR__ . '/training_data/file.txt'); // Load from the training_data directory
$markov = new MarkovChain();
$markov->train($prophecyText);

echo "📜 Your Eldritch Prophecy: " . $markov->generate() . PHP_EOL;
