// Pas d'échappement HTML ici : React échappe déjà à l'affichage, encoder avant l'envoi
// stockait des valeurs corrompues en base (O'Brien -> O&#x27;Brien).
export const trimText = (value) => (typeof value === 'string' ? value.trim() : value);
