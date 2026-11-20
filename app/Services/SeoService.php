<?php

namespace App\Services;

/**
 * Servicio ligero para gestionar metadatos SEO y Open Graph en feed.raupulus.dev.
 */
class SeoService
{
    private string $title = '';

    private string $description = '';

    private string $url = '';

    private string $image = '';

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getImage(): string
    {
        return $this->image;
    }
}
