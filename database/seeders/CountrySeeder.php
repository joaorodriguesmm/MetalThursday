<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

/**
 * Seeder para a tabela countries.
 *
 * @since 1.0
 * @version 1.0
 */
class CountrySeeder extends Seeder
{
    /**
     * Executa o seeder.
     *
     * @return void
     *
     * @version 1.0
     * @since 1.0
     */
    public function run(): void
    {
        $countries = [
            [
                'name' => 'Afeganistão',
                'code' => 'AF'
            ],
            [
                'name' => 'África do Sul',
                'code' => 'ZA'
            ],
            [
                'name' => 'Albânia',
                'code' => 'AL'
            ],
            [
                'name' => 'Alemanha',
                'code' => 'DE'
            ],
            [
                'name' => 'Andorra',
                'code' => 'AD'
            ],
            [
                'name' => 'Angola',
                'code' => 'AO'
            ],
            [
                'name' => 'Arábia Saudita',
                'code' => 'SA'
            ],
            [
                'name' => 'Argélia',
                'code' => 'DZ'
            ],
            [
                'name' => 'Argentina',
                'code' => 'AR'
            ],
            [
                'name' => 'Arménia',
                'code' => 'AM'
            ],
            [
                'name' => 'Austrália',
                'code' => 'AU'
            ],
            [
                'name' => 'Áustria',
                'code' => 'AT'
            ],
            [
                'name' => 'Azerbaijão',
                'code' => 'AZ'
            ],
            [
                'name' => 'Bahamas',
                'code' => 'BS'
            ],
            [
                'name' => 'Bangladexe',
                'code' => 'BD'
            ],
            [
                'name' => 'Barbados',
                'code' => 'BB'
            ],
            [
                'name' => 'Barém',
                'code' => 'BH'
            ],
            [
                'name' => 'Bélgica',
                'code' => 'BE'
            ],
            [
                'name' => 'Belize',
                'code' => 'BZ'
            ],
            [
                'name' => 'Benim',
                'code' => 'BJ'
            ],
            [
                'name' => 'Bielorrússia',
                'code' => 'BY'
            ],
            [
                'name' => 'Bolívia',
                'code' => 'BO'
            ],
            [
                'name' => 'Bósnia e Herzegovina',
                'code' => 'BA'
            ],
            [
                'name' => 'Botsuana',
                'code' => 'BW'
            ],
            [
                'name' => 'Brasil',
                'code' => 'BR'
            ],
            [
                'name' => 'Brunei',
                'code' => 'BN'
            ],
            [
                'name' => 'Bulgária',
                'code' => 'BG'
            ],
            [
                'name' => 'Burquina Faso',
                'code' => 'BF'
            ],
            [
                'name' => 'Burúndi',
                'code' => 'BI'
            ],
            [
                'name' => 'Butão',
                'code' => 'BT'
            ],
            [
                'name' => 'Cabo Verde',
                'code' => 'CV'
            ],
            [
                'name' => 'Camarões',
                'code' => 'CM'
            ],
            [
                'name' => 'Camboja',
                'code' => 'KH'
            ],
            [
                'name' => 'Canadá',
                'code' => 'CA'
            ],
            [
                'name' => 'Catar',
                'code' => 'QA'
            ],
            [
                'name' => 'Cazaquistão',
                'code' => 'KZ'
            ],
            [
                'name' => 'Chade',
                'code' => 'TD'
            ],
            [
                'name' => 'Chile',
                'code' => 'CL'
            ],
            [
                'name' => 'China',
                'code' => 'CN'
            ],
            [
                'name' => 'Chipre',
                'code' => 'CY'
            ],
            [
                'name' => 'Colômbia',
                'code' => 'CO'
            ],
            [
                'name' => 'Comores',
                'code' => 'KM'
            ],
            [
                'name' => 'Congo-Brazzaville',
                'code' => 'CG'
            ],
            [
                'name' => 'Congo-Kinshasa',
                'code' => 'CD'
            ],
            [
                'name' => 'Coreia do Norte',
                'code' => 'KP'
            ],
            [
                'name' => 'Coreia do Sul',
                'code' => 'KR'
            ],
            [
                'name' => 'Cosovo',
                'code' => 'XK'
            ],
            [
                'name' => 'Costa do Marfim',
                'code' => 'CI'
            ],
            [
                'name' => 'Costa Rica',
                'code' => 'CR'
            ],
            [
                'name' => 'Croácia',
                'code' => 'HR'
            ],
            [
                'name' => 'Cuaite',
                'code' => 'KW'
            ],
            [
                'name' => 'Cuba',
                'code' => 'CU'
            ],
            [
                'name' => 'Dinamarca',
                'code' => 'DK'
            ],
            [
                'name' => 'Dominica',
                'code' => 'DM'
            ],
            [
                'name' => 'Egito',
                'code' => 'EG'
            ],
            [
                'name' => 'Emirados Árabes Unidos',
                'code' => 'AE'
            ],
            [
                'name' => 'Equador',
                'code' => 'EC'
            ],
            [
                'name' => 'Eritreia',
                'code' => 'ER'
            ],
            [
                'name' => 'Eslováquia',
                'code' => 'SK'
            ],
            [
                'name' => 'Eslovénia',
                'code' => 'SI'
            ],
            [
                'name' => 'Espanha',
                'code' => 'ES'
            ],
            [
                'name' => 'EUA',
                'code' => 'US'
            ],
            [
                'name' => 'Estónia',
                'code' => 'EE'
            ],
            [
                'name' => 'Etiópia',
                'code' => 'ET'
            ],
            [
                'name' => 'Fiji',
                'code' => 'FJ'
            ],
            [
                'name' => 'Filipinas',
                'code' => 'PH'
            ],
            [
                'name' => 'Finlândia',
                'code' => 'FI'
            ],
            [
                'name' => 'França',
                'code' => 'FR'
            ],
            [
                'name' => 'Gabão',
                'code' => 'GA'
            ],
            [
                'name' => 'Gâmbia',
                'code' => 'GM'
            ],
            [
                'name' => 'Gana',
                'code' => 'GH'
            ],
            [
                'name' => 'Geórgia',
                'code' => 'GE'
            ],
            [
                'name' => 'Granada',
                'code' => 'GD'
            ],
            [
                'name' => 'Grécia',
                'code' => 'GR'
            ],
            [
                'name' => 'Guatemala',
                'code' => 'GT'
            ],
            [
                'name' => 'Guiana',
                'code' => 'GY'
            ],
            [
                'name' => 'Guiné',
                'code' => 'GN'
            ],
            [
                'name' => 'Guiné Equatorial',
                'code' => 'GQ'
            ],
            [
                'name' => 'Guiné-Bissau',
                'code' => 'GW'
            ],
            [
                'name' => 'Haiti',
                'code' => 'HT'
            ],
            [
                'name' => 'Honduras',
                'code' => 'HN'
            ],
            [
                'name' => 'Hungria',
                'code' => 'HU'
            ],
            [
                'name' => 'Iémen',
                'code' => 'YE'
            ],
            [
                'name' => 'Ilhas Marechal',
                'code' => 'MH'
            ],
            [
                'name' => 'Ilhas Salomão',
                'code' => 'SB'
            ],
            [
                'name' => 'Índia',
                'code' => 'IN'
            ],
            [
                'name' => 'Indonésia',
                'code' => 'ID'
            ],
            [
                'name' => 'Irão',
                'code' => 'IR'
            ],
            [
                'name' => 'Iraque',
                'code' => 'IQ'
            ],
            [
                'name' => 'Irlanda',
                'code' => 'IE'
            ],
            [
                'name' => 'Islândia',
                'code' => 'IS'
            ],
            [
                'name' => 'Israel',
                'code' => 'IL'
            ],
            [
                'name' => 'Itália',
                'code' => 'IT'
            ],
            [
                'name' => 'Jamaica',
                'code' => 'JM'
            ],
            [
                'name' => 'Japão',
                'code' => 'JP'
            ],
            [
                'name' => 'Jibuti',
                'code' => 'DJ'
            ],
            [
                'name' => 'Jordânia',
                'code' => 'JO'
            ],
            [
                'name' => 'Laus',
                'code' => 'LA'
            ],
            [
                'name' => 'Lesoto',
                'code' => 'LS'
            ],
            [
                'name' => 'Letónia',
                'code' => 'LV'
            ],
            [
                'name' => 'Líbano',
                'code' => 'LB'
            ],
            [
                'name' => 'Libéria',
                'code' => 'LR'
            ],
            [
                'name' => 'Líbia',
                'code' => 'LY'
            ],
            [
                'name' => 'Listenstaine',
                'code' => 'LI'
            ],
            [
                'name' => 'Lituânia',
                'code' => 'LT'
            ],
            [
                'name' => 'Luxemburgo',
                'code' => 'LU'
            ],
            [
                'name' => 'Macedónia do Norte',
                'code' => 'MK'
            ],
            [
                'name' => 'Madagáscar',
                'code' => 'MG'
            ],
            [
                'name' => 'Malásia',
                'code' => 'MY'
            ],
            [
                'name' => 'Maláui',
                'code' => 'MW'
            ],
            [
                'name' => 'Maldivas',
                'code' => 'MV'
            ],
            [
                'name' => 'Mali',
                'code' => 'ML'
            ],
            [
                'name' => 'Malta',
                'code' => 'MT'
            ],
            [
                'name' => 'Marrocos',
                'code' => 'MA'
            ],
            [
                'name' => 'Maurícia',
                'code' => 'MU'
            ],
            [
                'name' => 'Mauritânia',
                'code' => 'MR'
            ],
            [
                'name' => 'México',
                'code' => 'MX'
            ],
            [
                'name' => 'Mianmar',
                'code' => 'MM'
            ],
            [
                'name' => 'Micronésia',
                'code' => 'FM'
            ],
            [
                'name' => 'Moçambique',
                'code' => 'MZ'
            ],
            [
                'name' => 'Moldávia',
                'code' => 'MD'
            ],
            [
                'name' => 'Mónaco',
                'code' => 'MC'
            ],
            [
                'name' => 'Mongólia',
                'code' => 'MN'
            ],
            [
                'name' => 'Montenegro',
                'code' => 'ME'
            ],
            [
                'name' => 'Namíbia',
                'code' => 'NA'
            ],
            [
                'name' => 'Nauru',
                'code' => 'NR'
            ],
            [
                'name' => 'Nepal',
                'code' => 'NP'
            ],
            [
                'name' => 'Nicarágua',
                'code' => 'NI'
            ],
            [
                'name' => 'Níger',
                'code' => 'NE'
            ],
            [
                'name' => 'Nigéria',
                'code' => 'NG'
            ],
            [
                'name' => 'Noruega',
                'code' => 'NO'
            ],
            [
                'name' => 'Nova Zelândia',
                'code' => 'NZ'
            ],
            [
                'name' => 'Omã',
                'code' => 'OM'
            ],
            [
                'name' => 'Países Baixos',
                'code' => 'NL'
            ],
            [
                'name' => 'Palau',
                'code' => 'PW'
            ],
            [
                'name' => 'Panamá',
                'code' => 'PA'
            ],
            [
                'name' => 'Papua Nova Guiné',
                'code' => 'PG'
            ],
            [
                'name' => 'Paquistão',
                'code' => 'PK'
            ],
            [
                'name' => 'Peru',
                'code' => 'PE'
            ],
            [
                'name' => 'Polónia',
                'code' => 'PL'
            ],
            [
                'name' => 'Portugal',
                'code' => 'PT'
            ],
            [
                'name' => 'Quénia',
                'code' => 'KE'
            ],
            [
                'name' => 'Quirguistão',
                'code' => 'KG'
            ],
            [
                'name' => 'Quiribáti',
                'code' => 'KI'
            ],
            [
                'name' => 'Reino Unido',
                'code' => 'GB'
            ],
            [
                'name' => 'República Centro-Africana',
                'code' => 'CF'
            ],
            [
                'name' => 'República Checa',
                'code' => 'CZ'
            ],
            [
                'name' => 'República Dominicana',
                'code' => 'DO'
            ],
            [
                'name' => 'Roménia',
                'code' => 'RO'
            ],
            [
                'name' => 'Ruanda',
                'code' => 'RW'
            ],
            [
                'name' => 'Rússia',
                'code' => 'RU'
            ],
            [
                'name' => 'Salvador',
                'code' => 'SV'
            ],
            [
                'name' => 'Samoa',
                'code' => 'WS'
            ],
            [
                'name' => 'Santa Lúcia',
                'code' => 'LC'
            ],
            [
                'name' => 'São Cristóvão e Neves',
                'code' => 'KN'
            ],
            [
                'name' => 'São Marinho',
                'code' => 'SM'
            ],
            [
                'name' => 'São Tomé e Príncipe',
                'code' => 'ST'
            ],
            [
                'name' => 'São Vicente e Granadinas',
                'code' => 'VC'
            ],
            [
                'name' => 'Seicheles',
                'code' => 'SC'
            ],
            [
                'name' => 'Senegal',
                'code' => 'SN'
            ],
            [
                'name' => 'Serra Leoa',
                'code' => 'SL'
            ],
            [
                'name' => 'Sérvia',
                'code' => 'RS'
            ],
            [
                'name' => 'Singapura',
                'code' => 'SG'
            ],
            [
                'name' => 'Síria',
                'code' => 'SY'
            ],
            [
                'name' => 'Somália',
                'code' => 'SO'
            ],
            [
                'name' => 'Sri Lanca',
                'code' => 'LK'
            ],
            [
                'name' => 'Suazilândia',
                'code' => 'SZ'
            ],
            [
                'name' => 'Sudão',
                'code' => 'SD'
            ],
            [
                'name' => 'Sudão do Sul',
                'code' => 'SS'
            ],
            [
                'name' => 'Suécia',
                'code' => 'SE'
            ],
            [
                'name' => 'Suíça',
                'code' => 'CH'
            ],
            [
                'name' => 'Suriname',
                'code' => 'SR'
            ],
            [
                'name' => 'Tailândia',
                'code' => 'TH'
            ],
            [
                'name' => 'Taiuã',
                'code' => 'TW'
            ],
            [
                'name' => 'Tajiquistão',
                'code' => 'TJ'
            ],
            [
                'name' => 'Tanzânia',
                'code' => 'TZ'
            ],
            [
                'name' => 'Timor-Leste',
                'code' => 'TL'
            ],
            [
                'name' => 'Togo',
                'code' => 'TG'
            ],
            [
                'name' => 'Tonga',
                'code' => 'TO'
            ],
            [
                'name' => 'Trindade e Tobago',
                'code' => 'TT'
            ],
            [
                'name' => 'Tunísia',
                'code' => 'TN'
            ],
            [
                'name' => 'Turcomenistão',
                'code' => 'TM'
            ],
            [
                'name' => 'Turquia',
                'code' => 'TR'
            ],
            [
                'name' => 'Tuvalu',
                'code' => 'TV'
            ],
            [
                'name' => 'Ucrânia',
                'code' => 'UA'
            ],
            [
                'name' => 'Uganda',
                'code' => 'UG'
            ],
            [
                'name' => 'Uruguai',
                'code' => 'UY'
            ],
            [
                'name' => 'Usbequistão',
                'code' => 'UZ'
            ],
            [
                'name' => 'Vanuatu',
                'code' => 'VU'
            ],
            [
                'name' => 'Vaticano',
                'code' => 'VA'
            ],
            [
                'name' => 'Venezuela',
                'code' => 'VE'
            ],
            [
                'name' => 'Vietname',
                'code' => 'VN'
            ],
            [
                'name' => 'Zâmbia',
                'code' => 'ZM'
            ],
            [
                'name' => 'Zimbábue',
                'code' => 'ZW'
            ],
            [
                'name' => 'Internacional',
                'code' => 'XX'
            ],
        ];

        foreach ($countries as $countryData) {
            Country::updateOrCreate(
                ['code' => $countryData['code']],
                ['name' => $countryData['name']]
            );
        }
    }
}
