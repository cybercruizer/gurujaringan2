<?php

namespace App\Support;

use Illuminate\Support\Str;

class CommentCaptcha
{
    private const SESSION_KEY = 'comment_captcha';

    /**
     * @return array{token: string, first_number: int, second_number: int}
     */
    public function issue(): array
    {
        $firstNumber = random_int(1, 9);
        $secondNumber = random_int(1, 9);
        $token = Str::random(40);
        $challenges = session()->get(self::SESSION_KEY, []);

        $challenges = array_filter(
            $challenges,
            fn (array $challenge): bool => $challenge['expires_at'] >= now()->timestamp,
        );

        if (count($challenges) >= 20) {
            $challenges = array_slice($challenges, -19, null, true);
        }

        $challenges[$token] = [
            'answer' => $firstNumber + $secondNumber,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ];

        session()->put(self::SESSION_KEY, $challenges);

        return [
            'token' => $token,
            'first_number' => $firstNumber,
            'second_number' => $secondNumber,
        ];
    }

    public function consume(string $token): ?int
    {
        $challenge = session()->pull(self::SESSION_KEY.'.'.$token);

        if (
            ! is_array($challenge)
            || ! isset($challenge['answer'], $challenge['expires_at'])
            || $challenge['expires_at'] < now()->timestamp
        ) {
            return null;
        }

        return (int) $challenge['answer'];
    }
}
