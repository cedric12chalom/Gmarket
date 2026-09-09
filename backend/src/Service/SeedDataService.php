<?php

namespace App\Service;

class SeedDataService
{
    public function getCategories(): array
    {
        return [
            ['nom' => 'Cereales et derives', 'transport' => 'standard', 'fixe' => '1.00', 'pourcent' => '3.00'],
            ['nom' => 'Tubercules et racines', 'transport' => 'rapide', 'fixe' => '1.50', 'pourcent' => '4.00'],
            ['nom' => 'Legumes', 'transport' => 'rapide', 'fixe' => '1.50', 'pourcent' => '4.00'],
            ['nom' => 'Feuilles et legumes-feuilles', 'transport' => 'rapide', 'fixe' => '1.20', 'pourcent' => '4.00'],
            ['nom' => 'Fruits', 'transport' => 'rapide', 'fixe' => '2.00', 'pourcent' => '5.00'],
            ['nom' => 'Legumineuses', 'transport' => 'standard', 'fixe' => '1.20', 'pourcent' => '3.50'],
            ['nom' => 'Noix, graines et oleagineux', 'transport' => 'standard', 'fixe' => '1.80', 'pourcent' => '4.00'],
            ['nom' => 'Epices et condiments', 'transport' => 'standard', 'fixe' => '2.50', 'pourcent' => '7.00'],
            ['nom' => 'Produits de plantation', 'transport' => 'standard', 'fixe' => '2.00', 'pourcent' => '5.00'],
            ['nom' => 'Viandes', 'transport' => 'refrigere', 'fixe' => '5.00', 'pourcent' => '6.00'],
            ['nom' => 'Abats', 'transport' => 'refrigere', 'fixe' => '4.00', 'pourcent' => '5.00'],
            ['nom' => 'Poissons et produits de la peche', 'transport' => 'refrigere', 'fixe' => '4.50', 'pourcent' => '6.50'],
            ['nom' => 'Oeufs et produits laitiers', 'transport' => 'refrigere', 'fixe' => '4.00', 'pourcent' => '6.00'],
            ['nom' => 'Beurres', 'transport' => 'standard', 'fixe' => '3.00', 'pourcent' => '5.00'],
            ['nom' => 'Huiles', 'transport' => 'standard', 'fixe' => '2.60', 'pourcent' => '5.00'],
            ['nom' => 'Produits apicoles', 'transport' => 'standard', 'fixe' => '3.00', 'pourcent' => '5.00'],
        ];
    }

    public function getProducts(): array
    {
        $products = [];
        $products = array_merge($products, $this->cereales());
        $products = array_merge($products, $this->tubercules());
        $products = array_merge($products, $this->legumes());
        $products = array_merge($products, $this->feuilles());
        $products = array_merge($products, $this->fruits());
        $products = array_merge($products, $this->legumineuses());
        $products = array_merge($products, $this->noix());
        $products = array_merge($products, $this->epices());
        $products = array_merge($products, $this->plantation());
        $products = array_merge($products, $this->viandes());
        $products = array_merge($products, $this->abats());
        $products = array_merge($products, $this->poissons());
        $products = array_merge($products, $this->oeufs());
        $products = array_merge($products, $this->beurres());
        $products = array_merge($products, $this->huiles());
        $products = array_merge($products, $this->apicoles());
        return $products;
    }

    private function p(string $n, string $c, string $u, int $min, int $max, string $d): array
    {
        return ['n' => $n, 'c' => $c, 'u' => $u, 'min' => (string)$min, 'max' => (string)$max, 'd' => $d];
    }

    private function batch(string $c, string $u, int $min, int $max, string $d, array $noms): array
    {
        $r = [];
        foreach ($noms as $n) { $r[] = $this->p($n, $c, $u, $min, $max, $d); }
        return $r;
    }

    private function cereales(): array { $c = 'Cereales et derives'; return $this->batch($c, 'kg', 130, 1100, 'Cereale du terroir camerounais', ['Maïs','Maïs blanc','Maïs jaune','Maïs rouge','Maïs frais','Maïs en épis','Maïs séché','Riz local','Riz blanc','Riz complet','Riz rouge','Riz brun','Riz étuvé','Mil','Sorgho','Sorgho rouge','Sorgho blanc','Fonio','Blé','Avoine','Farine de maïs','Farine de mil','Farine de sorgho','Farine de blé','Farine de riz']); }

    private function tubercules(): array { $c = 'Tubercules et racines'; return $this->batch($c, 'kg', 100, 500, 'Tubercule frais du terroir', ['Manioc','Manioc doux','Manioc amer','Manioc frais','Manioc séché','Gari blanc','Gari jaune','Tapioca','Macabo','Macabo blanc','Macabo rouge','Taro','Igname','Igname blanche','Igname jaune','Patate douce','Patate douce blanche','Patate douce jaune','Patate douce violette','Pomme de terre','Pomme de terre nouvelle','Souchet','Gingembre','Curcuma frais']); }

    private function legumes(): array { $c = 'Legumes'; return $this->batch($c, 'kg', 150, 1200, 'Legume frais du marche', ['Tomate','Tomate cerise','Tomate locale','Oignon','Oignon rouge','Oignon blanc','Échalote','Ail','Carotte','Concombre','Courgette','Courge','Aubergine','Aubergine africaine','Aubergine blanche','Poivron vert','Poivron rouge','Poivron jaune','Piment rouge','Piment vert','Piment jaune','Piment antillais','Gombo','Haricot vert','Petit pois','Brocoli','Radis','Navet','Betterave']); }

    private function feuilles(): array { $c = 'Feuilles et legumes-feuilles'; return $this->batch($c, 'tas', 100, 400, 'Feuille fraiche du terroir', ['Ndolé','Folon','Eru','Épinard','Épinard africain','Feuilles de manioc','Feuilles de macabo','Feuilles de taro','Feuilles de patate douce','Feuilles de gombo','Feuilles de haricot','Feuilles de maïs','Morelle noire','Kelen-kelen','Waterleaf','Amarantes','Feuilles de courge','Feuilles de moringa','Feuilles de baobab','Persil','Coriandre','Basilic','Menthe','Citronnelle','Ciboulette']); }

    private function fruits(): array { $c = 'Fruits'; return $this->batch($c, 'kg', 100, 2500, 'Fruit frais de saison', ['Banane douce','Banane plantain','Banane plantain mûre','Banane plantain verte','Banane rouge','Ananas','Mangue','Mangue sauvage','Papaye','Orange','Orange douce','Mandarine','Clémentine','Citron','Citron vert','Pamplemousse','Pastèque','Melon','Avocat','Avocat doux','Noix de coco','Goyave','Corossol','Fruit de la passion','Fruit à pain','Prune africaine','Safou','Carambole','Canne à sucre','Pomme de Cythère','Tamarin','Baobab','Fraise','Raisin','Pomme']); }

    private function legumineuses(): array { $c = 'Legumineuses'; return $this->batch($c, 'kg', 350, 1200, 'Legumineuse riche en proteines', ['Haricot rouge','Haricot blanc','Haricot noir','Haricot tacheté','Haricot vert','Niébé','Soja','Pois chiche','Pois cassé','Lentille','Arachide','Arachide décortiquée','Arachide grillée','Arachide fraîche','Pois bambara','Voandzou']); }

    private function noix(): array { $c = 'Noix, graines et oleagineux'; return $this->batch($c, 'kg', 300, 2200, 'Noix ou graine oléagineuse', ['Noix de cajou','Noix de coco','Noix de karité','Noix de kola','Njansang','Graines de courge','Graines de melon','Graines de sésame','Graines de tournesol','Graines de soja','Graines de chia','Graines de lin','Graines de moringa','Graines de baobab','Graines de néré']); }

    private function epices(): array { $c = 'Epices et condiments'; return $this->batch($c, 'kg', 200, 2200, 'Epice ou condiment aromatique', ['Poivre noir','Poivre blanc','Poivre de Penja','Piment sec','Piment en poudre','Gingembre sec','Gingembre en poudre','Curcuma','Curcuma en poudre','Ail séché','Ail en poudre','Oignon séché','Oignon en poudre','Clou de girofle','Cannelle','Muscade','Anis','Fenouil','Laurier','Mbongo','Écorce de mbongo','Basilic séché','Citronnelle séchée']); }

    private function plantation(): array { $c = 'Produits de plantation'; return $this->batch($c, 'kg', 100, 1800, 'Produit de plantation du cameroun', ['Cacao','Fèves de cacao','Café arabica','Café robusta','Thé','Noix de cola','Noix de palme','Palmiste','Canne à sucre','Tabac','Caoutchouc naturel','Coton']); }

    private function viandes(): array { $c = 'Viandes'; return $this->batch($c, 'kg', 800, 5000, 'Viande fraiche de qualite', ['Viande de bœuf','Filet de bœuf','Faux-filet','Côte de bœuf','Steak de bœuf','Viande de veau','Viande de porc','Côte de porc','Filet de porc','Viande de chèvre','Viande de cabri','Viande de mouton','Côtelette de mouton','Viande d\'agneau','Viande de lapin','Poulet de chair','Poulet fermier','Poulet entier','Cuisses de poulet','Ailes de poulet','Blanc de poulet','Dinde','Viande de dinde','Canard','Viande de canard']); }

    private function abats(): array { $c = 'Abats'; return $this->batch($c, 'kg', 300, 2500, 'Abat frais de qualite', ['Foie de bœuf','Foie de porc','Foie de poulet','Foie de chèvre','Cœur de bœuf','Cœur de porc','Cœur de poulet','Rognons','Tripes','Langue de bœuf','Pied de bœuf','Pied de porc','Queue de bœuf','Queue de porc','Pattes de poulet']); }

    private function poissons(): array { $c = 'Poissons et produits de la peche'; return $this->batch($c, 'kg', 1000, 5500, 'Poisson ou fruits de mer frais', ['Tilapia','Silure','Poisson-chat','Carpe','Capitaine','Bar','Maquereau','Sardine','Morue','Hareng','Thon','Saumon','Poisson fumé','Poisson séché','Crevettes','Crevettes séchées','Écrevisses','Écrevisses séchées','Crabes','Escargots','Huîtres','Moules']); }

    private function oeufs(): array { $c = 'Oeufs et produits laitiers'; return $this->batch($c, 'pièce', 50, 2500, 'Oeuf ou produit laitier frais', ['Oeuf de poule','Oeufs de poule plateau','Oeuf de caille','Oeufs de caille plateau','Lait frais de vache','Lait frais de chèvre','Lait caillé','Lait fermenté','Yaourt nature','Yaourt aux fruits','Fromage frais','Fromage de chèvre','Fromage de vache','Crème fraîche','Beurre de lait']); }

    private function beurres(): array { $c = 'Beurres'; return $this->batch($c, 'kg', 800, 3500, 'Beurre naturel de qualite', ['Beurre de karité','Beurre de cacao','Beurre de lait','Beurre de cacahuète','Beurre de coco','Beurre d\'arachide','Beurre clarifié']); }

    private function huiles(): array { $c = 'Huiles'; return $this->batch($c, 'litre', 800, 4000, 'Huile alimentaire naturelle', ['Huile de palme','Huile de palmiste','Huile d\'arachide','Huile de soja','Huile de tournesol','Huile de maïs','Huile de coco','Huile de sésame','Huile de colza','Huile de coton','Huile de noix de cajou','Huile de karité','Huile de moringa','Huile de neem','Huile d\'avocat']); }

    private function apicoles(): array { $c = 'Produits apicoles'; return $this->batch($c, 'litre', 500, 5000, 'Produit apicole pur du cameroun', ['Miel','Miel de forêt','Miel de montagne','Miel de fleurs','Miel brut','Cire d\'abeille','Propolis','Pollen']); }
}
