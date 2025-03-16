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
                $this->chain[$pair][$nextWord] = $count / $total;
            }
        }
    }

    public function generate($maxWords = 15) {
        $validStartPairs = array_keys($this->chain);

        // Ensure we start with an actual phrase (not a random word)
        $currentPair = $validStartPairs[array_rand($validStartPairs)];
        $sentence = explode(' ', $currentPair);

        $minWords = 6; // Ensures a prophecy has at least 6 words
        $stopProbability = 0.25; // 25% chance to stop at punctuation (so it doesn’t always cut off)

        for ($i = 0; $i < $maxWords; $i++) {
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

            // Prevent early stopping
            if (count($sentence) < $minWords) {
                continue; // Keep generating words
            }

            // Stop only if a clean sentence ending is reached
            if (preg_match('/[.!?]+$/', end($sentence)) && mt_rand(1, 100) / 100.0 < $stopProbability) {
                break;
            }
        }

        // Ensure a clean sentence ending
        $lastWord = end($sentence);
        if (!preg_match('/[.!?]+$/', $lastWord)) {
            $sentence[] = '.';
        }

        return ucfirst(implode(' ', $sentence));
    }
}

// Load prophecy training data
$prophecyText = file_get_contents(__DIR__ . '/training_data/file.txt'); // Load from the training_data directory
$markov = new MarkovChain();
$markov->train($prophecyText);

echo "📜 Your Eldritch Prophecy: " . $markov->generate() . PHP_EOL;
