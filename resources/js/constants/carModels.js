// O'zbekistonda aktiv sotuvdagi avtomobil turlari (nomlari), markasi bo'yicha guruhlangan
const CAR_MODELS_BY_MAKE = {
    Chevrolet: [
        'Cobalt', 'Nexia 3', 'Malibu', 'Malibu 2', 'Spark', 'Matiz',
        'Damas', 'Labo', 'Lacetti', 'Gentra', 'Onix', 'Tracker',
        'Captiva', 'Monza', 'Equinox', 'TrailBlazer',
    ],
    BYD: [
        'Song Plus', 'Song Plus Champion', 'Chazor', 'Yuan Up',
        'Yuan Plus (Atto 3)', 'Qin Plus', 'Han', 'Tang', 'Seal',
        'Destroyer 05', 'Frigate 07',
    ],
    Kia: [
        'K5', 'Sportage', 'Sorento', 'Seltos', 'Soluto',
        'Cerato (K3)', 'Rio', 'Picanto', 'Carnival',
    ],
    Chery: ['Tiggo 4', 'Tiggo 7 Pro', 'Tiggo 8 Pro', 'Arrizo 5', 'Arrizo 6'],
    Haval: ['Jolion', 'M6', 'F7', 'H6', 'Dargo'],
    Hyundai: ['Elantra', 'Tucson', 'Santa Fe', 'Sonata', 'Accent', 'Solaris'],
    'Lada (VAZ)': ['Granta', 'Vesta', 'Niva', 'XRAY'],
};

// Multiselect uchun guruhlangan options ({label, options: [{value, label}]})
export const carModelGroups = Object.entries(CAR_MODELS_BY_MAKE).map(([make, models]) => ({
    label: make,
    options: models.map((model) => ({ value: model, label: model })),
}));

// Tekis ro'yxat — mavjud qiymatni tekshirish uchun (masalan, tahrirlash sahifasida)
export const carModelOptions = carModelGroups.flatMap((group) => group.options);
