<?php

namespace App\Http\OpenLottery;

use App\Models\OpenLottery;
use App\Models\OpenLotteryParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Who this browser is in an open lottery, with no accounts: the secrets it keeps in cookies
 * that only this lottery's pages receive, one for taking part and one for organizing.
 */
class OpenLotteryVisitor
{
    private const A_YEAR = 525600;

    private const PARTICIPANT = 'me';

    private const ORGANIZER = 'organizer';

    private ?OpenLotteryParticipant $participant = null;

    private bool $looked = false;

    public function __construct(private Request $request, private OpenLottery $lottery)
    {
    }

    /** Whoever this browser joined as, removed or not */
    public function participant(): ?OpenLotteryParticipant
    {
        if (! $this->looked) {
            $key = $this->participantKey();
            $this->participant = $key ? OpenLotteryParticipant::whereBelongsTo($this->lottery, 'lottery')->byKey($key)->first() : null;
            $this->looked = true;
        }

        return $this->participant;
    }

    /** Whoever this browser joined as, while they take part */
    public function takingPart(): ?OpenLotteryParticipant
    {
        $participant = $this->participant();

        return $participant && ! $participant->removed_at ? $participant : null;
    }

    public function wasRemoved(): bool
    {
        return (bool) $this->participant()?->removed_at;
    }

    public function organizes(): bool
    {
        return $this->lottery->isOrganizer($this->organizerKey());
    }

    public function participantKey(): ?string
    {
        return $this->cookie(self::PARTICIPANT);
    }

    public function organizerKey(): ?string
    {
        return $this->cookie(self::ORGANIZER);
    }

    /** This browser takes part as whoever holds this secret */
    public function rememberParticipant(string $key): void
    {
        $this->remember(self::PARTICIPANT, $key);
    }

    /** This browser organizes */
    public function rememberOrganizer(string $key): void
    {
        $this->remember(self::ORGANIZER, $key);
    }

    public function forgetParticipant(): void
    {
        Cookie::queue(Cookie::forget($this->cookieName(self::PARTICIPANT), $this->path()));
    }

    private function remember(string $who, string $key): void
    {
        Cookie::queue($this->cookieName($who), $key, self::A_YEAR, $this->path());
    }

    private function cookie(string $who): ?string
    {
        return $this->request->cookie($this->cookieName($who));
    }

    private function cookieName(string $who): string
    {
        return "open_{$who}_{$this->lottery->slug}";
    }

    private function path(): string
    {
        return "/l/{$this->lottery->slug}";
    }
}
