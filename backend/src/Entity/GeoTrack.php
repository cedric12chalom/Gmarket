<?php

namespace App\Entity;

use App\Repository\GeoTrackRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GeoTrackRepository::class)]
#[ORM\Table(name: 'geo_track', indexes: [
    new ORM\Index(name: 'idx_geo_lat_lng', columns: ['latitude', 'longitude']),
    new ORM\Index(name: 'idx_geo_updated_at', columns: ['updated_at']),
])]
class GeoTrack
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private ?string $longitude = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[ORM\Column]
    private ?int $referenceId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $emoji = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $routeGeojson = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $estimatedDistance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $estimatedDuration = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getReferenceId(): ?int
    {
        return $this->referenceId;
    }

    public function setReferenceId(int $referenceId): self
    {
        $this->referenceId = $referenceId;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getEmoji(): ?string
    {
        return $this->emoji;
    }

    public function setEmoji(?string $emoji): self
    {
        $this->emoji = $emoji;
        return $this;
    }

    public function getRouteGeojson(): ?string
    {
        return $this->routeGeojson;
    }

    public function setRouteGeojson(?string $routeGeojson): self
    {
        $this->routeGeojson = $routeGeojson;
        return $this;
    }

    public function getEstimatedDistance(): ?string
    {
        return $this->estimatedDistance;
    }

    public function setEstimatedDistance(?string $estimatedDistance): self
    {
        $this->estimatedDistance = $estimatedDistance;
        return $this;
    }

    public function getEstimatedDuration(): ?string
    {
        return $this->estimatedDuration;
    }

    public function setEstimatedDuration(?string $estimatedDuration): self
    {
        $this->estimatedDuration = $estimatedDuration;
        return $this;
    }
}
