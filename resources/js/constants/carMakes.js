// O'zbekistonda aktiv sotuvdagi avtomobil markalari
const CAR_MAKES = [
    'BMW', 'BYD', 'Changan', 'Chery', 'Chevrolet', 'Dongfeng', 'Exeed',
    'FAW', 'GAC', 'Genesis', 'Geely', 'Great Wall', 'Haval', 'Hyundai',
    'Isuzu', 'JAC', 'Jaecoo', 'Jetour', 'Kia', 'Lada (VAZ)', 'Lexus',
    'Mercedes-Benz', 'MG', 'Nissan', 'Omoda', 'Skoda', 'Tank', 'Toyota',
    'Volkswagen', 'Voyah',
];

export const carMakeOptions = CAR_MAKES.map((make) => ({ value: make, label: make }));
