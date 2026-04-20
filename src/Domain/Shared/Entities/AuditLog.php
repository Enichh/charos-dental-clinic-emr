<?php

namespace CharosEMR\Domain\Shared\Entities;

class AuditLog
{
    private ?int $id;
    private \DateTime $timestamp;
    private ?string $userId;
    private ?string $userEmail;
    private ?string $userRole;
    private string $action;
    private ?string $resourceType;
    private ?string $resourceId;
    private string $ipAddress;
    private ?string $userAgent;
    private ?array $details;
    private bool $success;

    public function __construct(
        ?int $id,
        \DateTime $timestamp,
        ?string $userId,
        ?string $userEmail,
        ?string $userRole,
        string $action,
        ?string $resourceType,
        ?string $resourceId,
        string $ipAddress,
        ?string $userAgent,
        ?array $details,
        bool $success = true
    ) {
        $this->id = $id;
        $this->timestamp = $timestamp;
        $this->userId = $userId;
        $this->userEmail = $userEmail;
        $this->userRole = $userRole;
        $this->action = $action;
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->details = $details;
        $this->success = $success;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    public function getUserRole(): ?string
    {
        return $this->userRole;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getResourceType(): ?string
    {
        return $this->resourceType;
    }

    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s'),
            'user_id' => $this->userId,
            'user_email' => $this->userEmail,
            'user_role' => $this->userRole,
            'action' => $this->action,
            'resource_type' => $this->resourceType,
            'resource_id' => $this->resourceId,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'details' => $this->details,
            'success' => $this->success
        ];
    }
}
