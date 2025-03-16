<?php

class ReinforcedMarkovChain {
    private $chain = [];

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
        $this->updateWeights($sentence); // RL update step
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

    private function updateWeights($sentence) {
        $score = $this->evaluateProphecy($sentence);
        $previousPairs = [];

        for ($i = 0; $i < count($sentence) - 2; $i++) {
            $pair = $sentence[$i] . ' ' . $sentence[$i + 1];
            $nextWord = $sentence[$i + 2];

            // Prevent repeated phrases from getting stronger
            if (isset($previousPairs[$pair])) {
                $score = max(-1, $score - 1); // Slightly lower repeated phrases
            } else {
                $previousPairs[$pair] = true;
            }

            if (isset($this->chain[$pair][$nextWord])) {
                $this->chain[$pair][$nextWord] += $score;
                if ($this->chain[$pair][$nextWord] < 1) {
                    $this->chain[$pair][$nextWord] = 1; // Prevent negative weights
                }
            }
        }
    }


    private function evaluateProphecy($sentence) {
        $lastWord = end($sentence);
        $strongEndings = ['darkness', 'death', 'eternal', 'whispers', 'shadows'];

        // Reward if it ends on a strong word
        if (in_array(strtolower($lastWord), $strongEndings)) {
            return 2;
        }

        // Penalize if too short
        if (count($sentence) < 6) {
            return -2;
        }

        // Penalize if nonsense words appear
        $badPhrases = ['the.', 'it.', 'is.', 'and.', 'but.', 'or.', 'at.', 'by.', 'in.', 'of.'];
        foreach ($badPhrases as $phrase) {
            if (strpos(strtolower(implode(' ', $sentence)), $phrase) !== false) {
                return -1;
            }
        }

        return 1; // Default reward for neutral sentences
    }
}

// Load prophecy training data
$prophecyText = file_get_contents(__DIR__ . '/training_data/file.txt');
$markov = new ReinforcedMarkovChain();
$markov->train($prophecyText);

echo "📜 Your Eldritch Prophecy: " . $markov->generate() . PHP_EOL;

