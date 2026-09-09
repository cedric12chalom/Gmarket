export const ROLE_EMOJIS: Record<string, string> = {
  acheteur: '🛒',
  producteur: '🧑‍🌾',
  livreur: '🛵',
  livreur_rapide: '🏍️',
  livreur_refrigere: '🚚',
};

export const PRODUIT_EMOJIS: [string[], string][] = [
  [['tomate', 'tomates', 'purée de tomate', 'sauce pimentée'], '🍅'],
  [['mangue', 'mangues'], '🥭'],
  [['banane', 'bananes', 'plantain'], '🍌'],
  [['ananas'], '🍍'],
  [['raisin', 'raisins secs'], '🍇'],
  [['pastèque'], '🍉'],
  [['melon'], '🍈'],
  [['fraise'], '🍓'],
  [['cerise'], '🍒'],
  [['pêche'], '🍑'],
  [['poire'], '🍐'],
  [['pomme', 'pomme de terre'], '🍎'],
  [['carotte', 'carottes', 'légume', 'légumes', 'concombre', 'concombres', 'poivron', 'poivrons', 'champignon', 'champignons de paris', 'pleurotes', 'morilles', 'champignons sauvages'], '🥕'],
  [['maïs', 'mais', 'semences de maïs', 'farine de maïs', 'popcorn'], '🌽'],
  [['œuf', 'œufs', 'œufs de poule', 'œufs de cane'], '🥚'],
  [['fromage', 'fromage frais', 'yaourt', 'lait frais', 'beurre'], '🧀'],
  [['pain', 'pain de mie', 'baguette', 'brioche', 'beignet', 'beignets', 'pains et viennoiseries'], '🍞'],
  [['bœuf', 'bœuf haché', 'porc fumé', 'agneau', 'poulet entier', 'viande', 'viandes'], '🥩'],
  [['poisson', 'poissons', 'tilapia', 'maquereau', 'sardine', 'sardines', 'crevette', 'crevettes'], '🐟'],
  [['riz'], '🍚'],
  [['pâtes'], '🍝'],
  [['haricot', 'haricots rouges', 'lentille', 'lentilles', 'pois chiches', 'niébé', 'légumineuses'], '🫘'],
  [['noix', 'noix de cajou', 'amande', 'amandes', 'arachide', 'arachides', 'sésame', 'tournesol', 'oléagineux'], '🥜'],
  [['miel', "miel d'acacia", 'miel de fleurs sauvages', 'sucre de canne', 'sirop de palme'], '🍯'],
  [['huile', "huile d'arachide", 'huile de palme'], '🫒'],
  [['bissap', 'nectar de bissap', 'kinkeliba', 'feuilles de kinkeliba'], '🫐'],
  [['jus', "jus d'orange", 'jus de mangue', 'jus de gingembre', 'boisson', 'boissons traditionnelles'], '🧃'],
  [['épice', 'épices', 'poivre noir', 'gingembre', 'cumin', 'curcuma', 'sel rose'], '🌶️'],
  [['herbe', 'herbes aromatiques', 'basilic', 'persil', 'menthe', 'coriandre', 'feuilles de moringa', 'feuilles de neem', 'plantes médicinales'], '🌿'],
  [['fleur', "fleurs d'hibiscus", 'fleurs comestibles', 'pétales de rose'], '🌸'],
  [['bois', 'bois de chauffe', 'charbon de bois', 'bois énergie', 'fourrage', 'foin', 'aliment pour bétail'], '🪵'],
  [['sac', 'sacs en osier', 'coffrets en bois', 'emballages artisanaux'], '📦'],
  [['cuir', 'cuirs et peaux', 'cuir brut', 'peau de vache'], '👜'],
];

export const DEFAULT_EMOJI = '🌿';

export const getProductEmoji = (productName: string): string => {
  const normalized = productName
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
  for (const [keywords, emoji] of PRODUIT_EMOJIS) {
    if (keywords.some((kw) => normalized.includes(kw))) {
      return emoji;
    }
  }
  return DEFAULT_EMOJI;
};

export const getRoleEmoji = (role: string, typeTransport?: string | null): string => {
  if (role === 'livreur') {
    if (typeTransport === 'rapide') return ROLE_EMOJIS.livreur_rapide;
    if (typeTransport === 'refrigere') return ROLE_EMOJIS.livreur_refrigere;
    return ROLE_EMOJIS.livreur;
  }
  return ROLE_EMOJIS[role] || '📍';
};