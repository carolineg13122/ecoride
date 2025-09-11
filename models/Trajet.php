<?php

class Trajet {
    public int $id;
    public string $depart;
    public string $adresse_depart;
    public string $destination;
    public string $adresse_arrivee;
    public string $date;
    public int $duree_minutes;
    public float $prix;
    public int $places;
    public ?string $statut;
    public ?string $marque;
    public ?string $modele;

    public function __construct(array $data) {
        $this->id = (int)($data['id'] ?? 0);
        $this->depart = $data['depart'] ?? '';
        $this->adresse_depart = $data['adresse_depart'] ?? '';
        $this->destination = $data['destination'] ?? '';
        $this->adresse_arrivee = $data['adresse_arrivee'] ?? '';
        $this->date = $data['date'] ?? '';
        $this->duree_minutes = (int)($data['duree_minutes'] ?? 0);
        $this->prix = isset($data['prix']) ? (float)$data['prix'] : 0.0;
        $this->places = (int)($data['places'] ?? 0);
        $this->statut = $data['statut'] ?? null;
        $this->marque = $data['marque'] ?? null;
        $this->modele = $data['modele'] ?? null;
    }

    public function resume(): string {
        return "{$this->depart} → {$this->destination} ({$this->prix} €)";
    }
}