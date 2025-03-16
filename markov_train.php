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
        $validStartWords = array_filter(array_keys($this->chain), function ($word) {
            return ctype_upper($word[0]); // Ensure the first letter is uppercase
        });

        if (empty($validStartWords)) {
            $validStartWords = array_keys($this->chain); // Fallback if no uppercase words
        }

        $currentWord = $validStartWords[array_rand($validStartWords)];
        $sentence = [$currentWord];

        echo print_r($this->chain[$currentWord],true);

        for ($i = 0; $i < $maxWords; $i++) {
            if (!isset($this->chain[$currentWord])) break;

            $nextWords = $this->chain[$currentWord];
            $randomPick = mt_rand(1, 100) / 100.0;
            $cumulative = 0;

            foreach ($nextWords as $word => $probability) {
                $cumulative += $probability;
                if ($randomPick <= $cumulative) {
                    $sentence[] = $word;
                    $currentWord = $word;
                    break;
                }
            }

            if (preg_match('/[.!?]$/', $currentWord)) break; // Stop at punctuation
        }

        return ucfirst(implode(' ', $sentence)) . '.';
    }
}

// Load prophecy training data
$prophecyText = file_get_contents(__DIR__ . '/training_data/file.txt');
$markov = new MarkovChain();
$markov->train($prophecyText);

echo "📜 Your Eldritch Prophecy: " . $markov->generate() . PHP_EOL;

