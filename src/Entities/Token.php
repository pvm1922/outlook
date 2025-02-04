<?php

declare(strict_types=1);

namespace Symplicity\Outlook\Entities;

use JsonSerializable;
use DateTimeImmutable;
use DateTimeInterface;
use Stringable;
use Symplicity\Outlook\Interfaces\Entity\TokenInterface;

class Token implements TokenInterface, Stringable, JsonSerializable
{
    protected string $accessToken;
    protected ?string $refreshToken = null;
    protected ?int $expiresIn = null;
    protected ?string $idToken = null;
    protected DateTimeImmutable $tokenReceivedOn;
    protected ?string $type = null;
    protected ?string $emailAddress = null;
    protected ?string $displayName = null;

    // Mutator
    public function setEmailAddress(?string $emailAddress): Token
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    public function setDisplayName(?string $displayName): Token
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function setType(?string $type): Token
    {
        $this->type = $type;
        return $this;
    }

    public function setAccessToken(string $accessToken): Token
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function setRefreshToken(?string $refreshToken): Token
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    public function setExpiresIn(?int $expiresIn): Token
    {
        $this->expiresIn = $expiresIn;
        return $this;
    }

    public function setIdToken(?string $idToken): Token
    {
        $this->idToken = $idToken;
        return $this;
    }

    public function setTokenReceivedOn(): Token
    {
        $this->tokenReceivedOn = new DateTimeImmutable('now');
        return $this;
    }

    // Mark Accessors
    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getExpiresIn(): ?int
    {
        return $this->expiresIn;
    }

    public function getIdToken(): ?string
    {
        return $this->idToken;
    }

    public function tokenReceivedOn(): DateTimeInterface
    {
        return $this->tokenReceivedOn;
    }

    public function __toString(): string
    {
        return $this->getDisplayName() ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'accessToken' => $this->accessToken,
            'refreshToken' => $this->refreshToken,
            'expiresIn' => $this->expiresIn,
            'idToken' => $this->idToken,
            'tokenReceivedOn' => $this->tokenReceivedOn,
            'type' => $this->type,
            'emailAddress' => $this->emailAddress,
            'displayName' => $this->displayName
        ];
    }
}
