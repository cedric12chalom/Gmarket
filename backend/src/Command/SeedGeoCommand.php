<?php

namespace App\Command;

use App\Entity\Acheteur;
use App\Entity\Administrateur;
use App\Entity\Commande;
use App\Entity\GeoTrack;
use App\Entity\LigneCommande;
use App\Entity\Livraison;
use App\Entity\Livreur;
use App\Entity\Lot;
use App\Entity\Producteur;
use App\Entity\Produit;
use App\Entity\Variete;
use App\Enum\MoyenDeplacement;
use App\Enum\StatutCommande;
use App\Enum\StatutLivraison;
use App\Enum\StatutLot;
use App\Enum\StatutVerificationLivreur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed:geo',
    description: 'Crée des données de démonstration complètes (admin, producteurs, livreurs, acheteurs, lots, commandes, livraisons) avec coordonnées GPS pour remplir la carte interactive.'
)]
class SeedGeoCommand extends Command
{
    /**
     * Mot de passe de démonstration commun à tous les comptes créés.
     * Conforme à la politique de mot de passe de l\'application (lettre + chiffre +
     * caractère spécial + 8 caractères minimum).
     */
    public const DEMO_PASSWORD = 'Terralink2026!';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $admin = $this->seedAdmin();
        $producteurs = $this->seedProducteurs();
        $livreurs = $this->seedLivreurs();
        $acheteurs = $this->seedAcheteurs();

        $this->entityManager->flush();

        $lots = $this->seedLots($producteurs);
        $this->entityManager->flush();

        foreach ($lots as $lot) {
            if ($lot instanceof Lot && !$lot->getQrCode()) {
                $lot->setQrCode($lot->genererQRCode());
            }
        }
        $this->entityManager->flush();

        $this->seedLivraisons($producteurs, $acheteurs, $lots, $livreurs);
        $this->entityManager->flush();

        $this->seedGeoTracks($producteurs, $livreurs);
        $this->entityManager->flush();

        $output->writeln(sprintf('Administrateur: %s (%s)', $admin?->getEmail() ?? 'none', self::DEMO_PASSWORD));
        $output->writeln(sprintf('Producteurs: %d', count($producteurs)));
        $output->writeln(sprintf('Livreurs: %d', count($livreurs)));
        $output->writeln(sprintf('Acheteurs: %d', count($acheteurs)));
        $output->writeln(sprintf('Lots: %d', count($lots)));
        $output->writeln('Livraisons et positions GPS temps réel créées.');
        $output->writeln(sprintf('Mot de passe commun à tous les comptes de démo : %s', self::DEMO_PASSWORD));

        return Command::SUCCESS;
    }

    private function seedAdmin(): ?Administrateur
    {
        $repo = $this->entityManager->getRepository(Administrateur::class);
        $admin = $repo->findOneBy(['email' => 'admin@terralink.cm']);
        if (!$admin instanceof Administrateur) {
            $admin = new Administrateur();
            $admin->setEmail('admin@terralink.cm');
            $admin->setPassword($this->passwordHasher->hashPassword($admin, self::DEMO_PASSWORD));
            $admin->setRoles(['ROLE_USER']);
            $this->entityManager->persist($admin);
        }
        $admin->setNom('TerraLink');
        $admin->setPrenom('Administrateur');
        $admin->setTelephone('690000000');
        $admin->setLocalisation('Yaoundé (siège)');
        $admin->setVille('Yaoundé');
        $admin->setQuartier('Centre-ville');
        $admin->setCguAccepteLe(new \DateTime());
        return $admin;
    }

    private function seedProducteurs(): array
    {
        $repo = $this->entityManager->getRepository(Producteur::class);
        $definitions = [
            ['email' => 'george.ndongo@terralink.cm', 'nom' => 'Ndongo', 'prenom' => 'Georges', 'localisation' => 'Yaoundé (Mfoundi)', 'ville' => 'Yaoundé', 'quartier' => 'Nkolbisson', 'lat' => 3.848, 'lng' => 11.502, 'description' => 'Ferme maraîchère de plein champ', 'telephone' => '690000001'],
            ['email' => 'marie.essomba@terralink.cm', 'nom' => 'Essomba', 'prenom' => 'Marie', 'localisation' => 'Douala (Wouri)', 'ville' => 'Douala', 'quartier' => 'Akwa', 'lat' => 4.051, 'lng' => 9.768, 'description' => 'Arboriculture fruitière et vergers', 'telephone' => '690000002'],
            ['email' => 'paul.fotso@terralink.cm', 'nom' => 'Fotso', 'prenom' => 'Paul', 'localisation' => 'Bafoussam (Ouest)', 'ville' => 'Bafoussam', 'quartier' => 'Tamdja', 'lat' => 5.477, 'lng' => 10.417, 'description' => 'Cultures vivrières et tubercules', 'telephone' => '690000003'],
            ['email' => 'amina.bello@terralink.cm', 'nom' => 'Bello', 'prenom' => 'Amina', 'localisation' => 'Garoua (Nord)', 'ville' => 'Garoua', 'quartier' => 'Ville de Garoua', 'lat' => 9.301, 'lng' => 13.395, 'description' => 'Céréales et élevage', 'telephone' => '690000004'],
            ['email' => 'jean.tchoua@terralink.cm', 'nom' => 'Tchoua', 'prenom' => 'Jean', 'localisation' => 'Bamenda (Nord-Ouest)', 'ville' => 'Bamenda', 'quartier' => 'Commercial Avenue', 'lat' => 5.959, 'lng' => 10.146, 'description' => 'Produits laitiers et apiculture', 'telephone' => '690000005'],
            ['email' => 'solenne.wamba@terralink.cm', 'nom' => 'Wamba', 'prenom' => 'Solenn', 'localisation' => 'Maroua (Extrême-Nord)', 'ville' => 'Maroua', 'quartier' => 'Domayo', 'lat' => 10.591, 'lng' => 14.316, 'description' => 'Épices, arachides et légumineuses', 'telephone' => '690000006'],
        ];

        $producteurs = [];
        foreach ($definitions as $def) {
            $producteur = $repo->findOneBy(['email' => $def['email']]);
            if (!$producteur instanceof Producteur) {
                $producteur = new Producteur();
                $producteur->setEmail($def['email']);
                $producteur->setPassword($this->passwordHasher->hashPassword($producteur, self::DEMO_PASSWORD));
                $producteur->setRoles(['ROLE_USER']);
                $this->entityManager->persist($producteur);
            }
            $producteur->setNom($def['nom']);
            $producteur->setPrenom($def['prenom']);
            $producteur->setTelephone($def['telephone']);
            $producteur->setLocalisation($def['localisation']);
            $producteur->setVille($def['ville']);
            $producteur->setQuartier($def['quartier']);
            $producteur->setDescriptionExploitation($def['description']);
            $producteur->setLatitude((string) $def['lat']);
            $producteur->setLongitude((string) $def['lng']);
            $producteur->setCguAccepteLe(new \DateTime());
            $producteurs[] = $producteur;
        }
        return $producteurs;
    }

    private function seedLivreurs(): array
    {
        $repo = $this->entityManager->getRepository(Livreur::class);
        $definitions = [
            ['email' => 'livreur.yaounde@terralink.cm', 'nom' => 'Kamdem', 'prenom' => 'Boris', 'ville' => 'Yaoundé', 'quartier' => 'Mvog-Ada', 'lat' => 3.8664, 'lng' => 11.5167, 'type_transport' => 'rapide', 'moyen_deplacement' => MoyenDeplacement::MOTO->value, 'cni_numero' => '115423876543', 'vehicule_plaque' => 'LT 401 YY', 'vehicule_marque_modele' => 'Yamaha DT 125', 'telephone_service' => '695000001'],
            ['email' => 'livreur.douala@terralink.cm', 'nom' => 'Mbah', 'prenom' => 'Cédric', 'ville' => 'Douala', 'quartier' => 'Akwa', 'lat' => 4.0447, 'lng' => 9.7063, 'type_transport' => 'standard', 'moyen_deplacement' => MoyenDeplacement::VOITURE->value, 'cni_numero' => '115424876544', 'vehicule_plaque' => 'LT 110 ZK', 'vehicule_marque_modele' => 'Toyota Corolla', 'telephone_service' => '695000002'],
            ['email' => 'livreur.bafoussam@terralink.cm', 'nom' => 'Siani', 'prenom' => 'Aïcha', 'ville' => 'Bafoussam', 'quartier' => 'Tamdja', 'lat' => 5.4770, 'lng' => 10.4173, 'type_transport' => 'refrigere', 'moyen_deplacement' => MoyenDeplacement::CAMIONNETTE->value, 'cni_numero' => '115425876545', 'vehicule_plaque' => 'LT 730 AB', 'vehicule_marque_modele' => 'Peugeot Partner', 'telephone_service' => '695000003'],
            ['email' => 'livreur.bamenda@terralink.cm', 'nom' => 'Nfor', 'prenom' => 'Emmanuel', 'ville' => 'Bamenda', 'quartier' => 'Commercial Avenue', 'lat' => 5.9599, 'lng' => 10.1460, 'type_transport' => 'standard', 'moyen_deplacement' => MoyenDeplacement::VELO->value, 'cni_numero' => '115426876546', 'vehicule_plaque' => null, 'vehicule_marque_modele' => 'VTT B\'TWIN', 'telephone_service' => '695000004'],
        ];

        $livreurs = [];
        foreach ($definitions as $def) {
            $livreur = $repo->findOneBy(['email' => $def['email']]);
            if (!$livreur instanceof Livreur) {
                $livreur = new Livreur();
                $livreur->setEmail($def['email']);
                $livreur->setPassword($this->passwordHasher->hashPassword($livreur, self::DEMO_PASSWORD));
                $livreur->setRoles(['ROLE_USER']);
                $this->entityManager->persist($livreur);
            }
            $livreur->setNom($def['nom']);
            $livreur->setPrenom($def['prenom']);
            $livreur->setTelephone($def['telephone_service']);
            $livreur->setLocalisation($def['ville']);
            $livreur->setVille($def['ville']);
            $livreur->setQuartier($def['quartier']);
            $livreur->setTypeTransport($def['type_transport']);
            $livreur->setLatitude((string) $def['lat']);
            $livreur->setLongitude((string) $def['lng']);
            $livreur->setCguAccepteLe(new \DateTime());

            $livreur->setDisponible(true);
            $livreur->setChatActif(true);
            $livreur->setTelephoneService($def['telephone_service']);
            $livreur->setCniNumero($def['cni_numero']);
            $livreur->setCniPhoto('https://placehold.co/600x400/9FE870/07362A?text=CNI');
            $livreur->setPhotoProfil('https://i.pravatar.cc/300');
            $livreur->setMoyenDeplacement($def['moyen_deplacement']);
            $livreur->setVehiculePlaque($def['vehicule_plaque']);
            $livreur->setVehiculeMarqueModele($def['vehicule_marque_modele']);
            $livreur->setMobileMoneyNumero($def['telephone_service']);
            $livreur->setContactUrgenceNom('Contact famille ' . $def['prenom']);
            $livreur->setContactUrgenceTelephone('6955000' . random_int(10, 99));
            $livreur->setConditionsLivraisonAccepteLe(new \DateTime());
            $livreur->setStatutVerification(StatutVerificationLivreur::VALIDE->value);
            $livreur->setVerifieLe(new \DateTime('-3 days'));
            $livreur->setValideLe(new \DateTime('-3 days'));
            $livreurs[] = $livreur;
        }
        return $livreurs;
    }

    private function seedAcheteurs(): array
    {
        $repo = $this->entityManager->getRepository(Acheteur::class);
        $definitions = [
            ['email' => 'acheteur.yaounde@terralink.cm', 'nom' => 'Ngono', 'prenom' => 'Clarisse', 'ville' => 'Yaoundé', 'quartier' => 'Mvog-Ada', 'lat' => 3.8650, 'lng' => 11.5210, 'adresse' => 'Rue 1899, Mvog-Ada, Yaoundé', 'telephone' => '696000001'],
            ['email' => 'acheteur.douala@terralink.cm', 'nom' => 'Djob', 'prenom' => 'Steve', 'ville' => 'Douala', 'quartier' => 'Akwa', 'lat' => 4.0500, 'lng' => 9.7700, 'adresse' => 'Akwa, Douala', 'telephone' => '696000002'],
            ['email' => 'acheteur.limbe@terralink.cm', 'nom' => 'Enow', 'prenom' => 'Dorcas', 'ville' => 'Limbé', 'quartier' => 'Mile 2', 'lat' => 4.0130, 'lng' => 9.2150, 'adresse' => 'Mile 2, Limbé', 'telephone' => '696000003'],
            ['email' => 'acheteur.bafoussam@terralink.cm', 'nom' => 'Mbarga', 'prenom' => 'Nadège', 'ville' => 'Bafoussam', 'quartier' => 'Tamdja', 'lat' => 5.4770, 'lng' => 10.4173, 'adresse' => 'Tamdja, Bafoussam', 'telephone' => '696000004'],
        ];

        $acheteurs = [];
        foreach ($definitions as $def) {
            $acheteur = $repo->findOneBy(['email' => $def['email']]);
            if (!$acheteur instanceof Acheteur) {
                $acheteur = new Acheteur();
                $acheteur->setEmail($def['email']);
                $acheteur->setPassword($this->passwordHasher->hashPassword($acheteur, self::DEMO_PASSWORD));
                $acheteur->setRoles(['ROLE_USER']);
                $this->entityManager->persist($acheteur);
            }
            $acheteur->setNom($def['nom']);
            $acheteur->setPrenom($def['prenom']);
            $acheteur->setTelephone($def['telephone']);
            $acheteur->setLocalisation($def['ville']);
            $acheteur->setVille($def['ville']);
            $acheteur->setQuartier($def['quartier']);
            $acheteur->setAdresseLivraison($def['adresse']);
            $acheteur->setLatitude((string) $def['lat']);
            $acheteur->setLongitude((string) $def['lng']);
            $acheteur->setCguAccepteLe(new \DateTime());
            $acheteurs[] = $acheteur;
        }
        return $acheteurs;
    }

    private function seedLots(array $producteurs): array
    {
        $produitRepo = $this->entityManager->getRepository(Produit::class);
        $lotRepo = $this->entityManager->getRepository(Lot::class);
        $varieteRepo = $this->entityManager->getRepository(Variete::class);

        $produits = [
            'Bananes', 'Mangues', 'Tomates', 'Carottes', 'Poivrons', 'Maïs', 'Mil',
            'Lait frais', 'Fromage frais', 'Miel d\'acacia', 'Arachides', 'Poivre noir',
            'Igname', 'Sardines', 'Poulet entier', 'Yaourt', 'Œufs de poule',
        ];

        $lots = [];
        $productToProducer = [
            'Bananes' => 1, 'Mangues' => 1, 'Tomates' => 0, 'Carottes' => 0, 'Poivrons' => 0,
            'Maïs' => 3, 'Mil' => 3, 'Lait frais' => 4, 'Fromage frais' => 4, 'Miel d\'acacia' => 4,
            'Arachides' => 5, 'Poivre noir' => 5, 'Igname' => 2, 'Sardines' => 1,
            'Poulet entier' => 3, 'Yaourt' => 4, 'Œufs de poule' => 2,
        ];

        foreach ($produits as $index => $produitNom) {
            $produit = $produitRepo->findOneBy(['nom' => $produitNom]);
            if (!$produit instanceof Produit) {
                continue;
            }
            $producerIndex = $productToProducer[$produitNom] ?? ($index % count($producteurs));
            $producteur = $producteurs[$producerIndex] ?? $producteurs[0];

            $existing = $lotRepo->findOneBy(['produit' => $produit->getId(), 'producteur' => $producteur->getId()]);
            if ($existing instanceof Lot) {
                $this->assignDemoVariety($existing, $produitNom, $varieteRepo);
                $lots[] = $existing;
                continue;
            }

            $lot = new Lot();
            $lot->setProduit($produit);
            $lot->setProducteur($producteur);
            $lot->setQuantiteDisponible((string) random_int(50, 500));
            $lot->setQuantiteReservee('0.00');
            // Prix cohérent avec les bornes du produit (RG01) : 60% entre min et max.
            $prixMin = (float) $produit->getPrixMin();
            $prixMax = (float) $produit->getPrixMax();
            $lot->setPrixProducteur((string) round($prixMin + ($prixMax - $prixMin) * 0.6, 2));
            // Récolte récente (0-2 jours) : lots frais, jamais "périmés" d'office.
            $lot->setDateRecolte(new \DateTime('-' . random_int(0, 2) . ' days'));
            $lot->setDureeConservation(random_int(5, 30));
            $lot->setStatut(StatutLot::DISPONIBLE->value);
            $lot->setLieuProduction($producteur->getLocalisation());
            $lot->setLatitude($producteur->getLatitude());
            $lot->setLongitude($producteur->getLongitude());
            $this->assignDemoVariety($lot, $produitNom, $varieteRepo);
            $this->entityManager->persist($lot);
            $lots[] = $lot;
        }
        return $lots;
    }

    private function assignDemoVariety(Lot $lot, string $produitNom, object $varieteRepo): void
    {
        if ($lot->getVariete() instanceof Variete || !($lot->getProduit() instanceof Produit)) {
            return;
        }

        $varieteNom = $this->getDemoVarietyName($produitNom);
        if ($varieteNom === null) {
            return;
        }

        $variete = $varieteRepo->findOneBy(['produit' => $lot->getProduit()->getId(), 'nom' => $varieteNom]);
        if ($variete instanceof Variete) {
            $lot->setVariete($variete);
        }
    }

    private function getDemoVarietyName(string $produitNom): ?string
    {
        return match ($produitNom) {
            'Bananes' => 'French',
            'Mangues' => 'Kent',
            default => null,
        };
    }

    private function seedLivraisons(array $producteurs, array $acheteurs, array $lots, array $livreurs): void
    {
        // Chaque livreur sert des acheteurs de sa propre ville : les itinéraires
        // de la carte sont ainsi courts, réalistes et lisibles au zoom ville.
        $pairs = [
            [0, 0], [0, 0], // Boris (Yaoundé) → Clarisse (Yaoundé)
            [1, 1], [1, 2], // Cédric (Douala) → Steve (Douala) + Dorcas (Limbé)
            [2, 3], [2, 3], // Aïcha (Bafoussam) → Nadège (Bafoussam)
            [3, 3], [3, 3], // Emmanuel (Bamenda) → Nadège (Bafoussam, ~60 km)
        ];
        $created = 0;
        foreach ($pairs as [$li, $ai]) {
            if (!isset($livreurs[$li]) || !isset($acheteurs[$ai])) continue;
            $livreur = $livreurs[$li];
            $acheteur = $acheteurs[$ai];

            // Choisir un lot chez le producteur le plus proche de l'acheteur
            // (retrait local, itinéraire court et lisible).
            $candidats = $this->lotsProchesDe($lots, $producteurs, $acheteur);

            $commande = new Commande();
            $commande->setAcheteur($acheteur);
            $commande->setModeRecuperation('livraison');
            $commande->setStatut(StatutCommande::EN_COURS->value);
            $montant = 0.0;

            $lot = $candidats[array_rand($candidats)];
            $ligne = new LigneCommande();
            $ligne->setLot($lot);
            $ligne->setQuantite(random_int(1, 5));
            $ligne->setPrixUnitaire((float) $lot->getPrixProducteur());
            $ligne->setSousTotal($ligne->getQuantite() * $ligne->getPrixUnitaire());
            $commande->addLigne($ligne);
            $montant += $ligne->getSousTotal();

            $commande->setMontantProduits((string) $montant);
            $commande->setCommission('0.00');
            $commande->setFraisLivraison((string) random_int(500, 1500));
            $commande->setMontantTotal((string) ($montant + (float) $commande->getFraisLivraison()));
            $this->entityManager->persist($commande);

            $livraison = new Livraison();
            $livraison->setCommande($commande);
            $livraison->setLivreur($livreur);
            $livraison->setStatut(StatutLivraison::EN_COURS->value);
            $livraison->setAdresseLivraison($acheteur->getAdresseLivraison());
            $livraison->setFrais((string) $commande->getFraisLivraison());
            $this->entityManager->persist($livraison);
            $created++;
        }
    }

    private function lotsProchesDe(array $lots, array $producteurs, Acheteur $acheteur): array
    {
        $latA = (float) $acheteur->getLatitude();
        $lngA = (float) $acheteur->getLongitude();

        usort($producteurs, function (Producteur $a, Producteur $b) use ($latA, $lngA) {
            return $this->distanceKm((float) $a->getLatitude(), (float) $a->getLongitude(), $latA, $lngA)
                <=> $this->distanceKm((float) $b->getLatitude(), (float) $b->getLongitude(), $latA, $lngA);
        });

        $nearest = $producteurs[0];
        $id = $nearest->getId();
        $candidats = array_values(array_filter($lots, fn (Lot $lot) => $lot->getProducteur()?->getId() === $id));
        if (empty($candidats) && count($producteurs) > 1) {
            $candidats = array_values(array_filter($lots, fn (Lot $lot) => $lot->getProducteur()?->getId() === $producteurs[1]->getId()));
        }
        return $candidats ?: $lots;
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function seedGeoTracks(array $producteurs, array $livreurs): void
    {
        $geoRepo = $this->entityManager->getRepository(GeoTrack::class);

        foreach ($producteurs as $producteur) {
            $existing = $geoRepo->findOneBy(['userId' => $producteur->getId(), 'type' => 'producer']);
            $track = $existing ?? new GeoTrack();
            $track->setUserId($producteur->getId());
            $track->setReferenceId($producteur->getId());
            $track->setType('producer');
            $track->setLatitude((string) $producteur->getLatitude());
            $track->setLongitude((string) $producteur->getLongitude());
            $track->setUpdatedAt(new \DateTime());
            $track->setEmoji('🧑‍🌾');
            if (!$existing) {
                $this->entityManager->persist($track);
            }
        }

        foreach ($livreurs as $livreur) {
            $existing = $geoRepo->findOneBy(['userId' => $livreur->getId(), 'type' => 'delivery_person']);
            $track = $existing ?? new GeoTrack();
            $track->setUserId($livreur->getId());
            $track->setReferenceId($livreur->getId());
            $track->setType('delivery_person');
            $track->setLatitude((string) $livreur->getLatitude());
            $track->setLongitude((string) $livreur->getLongitude());
            $track->setUpdatedAt(new \DateTime());
            $track->setEmoji('🚚');
            if (!$existing) {
                $this->entityManager->persist($track);
            }
        }
    }
}
