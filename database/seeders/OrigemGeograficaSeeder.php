<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Regista as origens geográficas utilizadas pela aplicação.
 *
 * Uma origem pode representar um país, uma nação constituinte, um território
 * ou uma origem internacional agregada. O nome `Seeder` permanece em inglês
 * por corresponder à convenção utilizada pelo Laravel.
 *
 * @since 2.0.0
 */
final class OrigemGeograficaSeeder extends Seeder
{
    /**
     * Origens geográficas indexadas pelo respetivo código.
     *
     * Os países utilizam, sempre que aplicável, códigos ISO 3166-1 alfa-2.
     * As nações constituintes do Reino Unido utilizam códigos ISO 3166-2, o
     * Kosovo utiliza `XK` e a origem agregada internacional utiliza `INT`.
     *
     * @var array<string, string>
     *
     * @since 2.0.0
     */
    private const ORIGENS_POR_CODIGO = [
        'AF' => 'Afeganistão',
        'ZA' => 'África do Sul',
        'AL' => 'Albânia',
        'DE' => 'Alemanha',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AG' => 'Antígua e Barbuda',
        'SA' => 'Arábia Saudita',
        'DZ' => 'Argélia',
        'AR' => 'Argentina',
        'AM' => 'Arménia',
        'AU' => 'Austrália',
        'AT' => 'Áustria',
        'AZ' => 'Azerbaijão',
        'BS' => 'Bahamas',
        'BD' => 'Bangladexe',
        'BB' => 'Barbados',
        'BH' => 'Barém',
        'BE' => 'Bélgica',
        'BZ' => 'Belize',
        'BJ' => 'Benim',
        'BY' => 'Bielorrússia',
        'BO' => 'Bolívia',
        'BA' => 'Bósnia e Herzegovina',
        'BW' => 'Botsuana',
        'BR' => 'Brasil',
        'BN' => 'Brunei',
        'BG' => 'Bulgária',
        'BF' => 'Burquina Faso',
        'BI' => 'Burúndi',
        'BT' => 'Butão',
        'CV' => 'Cabo Verde',
        'CM' => 'Camarões',
        'KH' => 'Camboja',
        'CA' => 'Canadá',
        'QA' => 'Catar',
        'KZ' => 'Cazaquistão',
        'TD' => 'Chade',
        'CZ' => 'Chéquia',
        'CL' => 'Chile',
        'CN' => 'China',
        'CY' => 'Chipre',
        'CO' => 'Colômbia',
        'KM' => 'Comores',
        'CG' => 'Congo-Brazzaville',
        'CD' => 'Congo-Kinshasa',
        'KP' => 'Coreia do Norte',
        'KR' => 'Coreia do Sul',
        'CI' => 'Costa do Marfim',
        'CR' => 'Costa Rica',
        'HR' => 'Croácia',
        'CU' => 'Cuba',
        'DK' => 'Dinamarca',
        'DM' => 'Dominica',
        'EG' => 'Egito',
        'SV' => 'El Salvador',
        'AE' => 'Emirados Árabes Unidos',
        'EC' => 'Equador',
        'ER' => 'Eritreia',
        'GB-SCT' => 'Escócia',
        'SK' => 'Eslováquia',
        'SI' => 'Eslovénia',
        'ES' => 'Espanha',
        'SZ' => 'Essuatíni',
        'US' => 'Estados Unidos',
        'EE' => 'Estónia',
        'ET' => 'Etiópia',
        'FJ' => 'Fiji',
        'PH' => 'Filipinas',
        'FI' => 'Finlândia',
        'FR' => 'França',
        'GA' => 'Gabão',
        'GM' => 'Gâmbia',
        'GH' => 'Gana',
        'GE' => 'Geórgia',
        'GD' => 'Granada',
        'GR' => 'Grécia',
        'GT' => 'Guatemala',
        'GY' => 'Guiana',
        'GN' => 'Guiné',
        'GQ' => 'Guiné Equatorial',
        'GW' => 'Guiné-Bissau',
        'HT' => 'Haiti',
        'HN' => 'Honduras',
        'HU' => 'Hungria',
        'YE' => 'Iémen',
        'MH' => 'Ilhas Marechal',
        'SB' => 'Ilhas Salomão',
        'IN' => 'Índia',
        'ID' => 'Indonésia',
        'GB-ENG' => 'Inglaterra',
        'IR' => 'Irão',
        'IQ' => 'Iraque',
        'IE' => 'Irlanda',
        'GB-NIR' => 'Irlanda do Norte',
        'IS' => 'Islândia',
        'IL' => 'Israel',
        'IT' => 'Itália',
        'JM' => 'Jamaica',
        'JP' => 'Japão',
        'DJ' => 'Jibuti',
        'JO' => 'Jordânia',
        'XK' => 'Kosovo',
        'KW' => 'Koweit',
        'LA' => 'Laos',
        'LS' => 'Lesoto',
        'LV' => 'Letónia',
        'LB' => 'Líbano',
        'LR' => 'Libéria',
        'LY' => 'Líbia',
        'LI' => 'Listenstaine',
        'LT' => 'Lituânia',
        'LU' => 'Luxemburgo',
        'MK' => 'Macedónia do Norte',
        'MG' => 'Madagáscar',
        'MY' => 'Malásia',
        'MW' => 'Maláui',
        'MV' => 'Maldivas',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MA' => 'Marrocos',
        'MU' => 'Maurícia',
        'MR' => 'Mauritânia',
        'MX' => 'México',
        'MM' => 'Mianmar',
        'FM' => 'Micronésia',
        'MZ' => 'Moçambique',
        'MD' => 'Moldávia',
        'MC' => 'Mónaco',
        'MN' => 'Mongólia',
        'ME' => 'Montenegro',
        'NA' => 'Namíbia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NI' => 'Nicarágua',
        'NE' => 'Níger',
        'NG' => 'Nigéria',
        'NO' => 'Noruega',
        'NZ' => 'Nova Zelândia',
        'OM' => 'Omã',
        'GB-WLS' => 'País de Gales',
        'NL' => 'Países Baixos',
        'PW' => 'Palau',
        'PS' => 'Palestina',
        'PA' => 'Panamá',
        'PG' => 'Papua Nova Guiné',
        'PK' => 'Paquistão',
        'PY' => 'Paraguai',
        'PE' => 'Peru',
        'PL' => 'Polónia',
        'PT' => 'Portugal',
        'KE' => 'Quénia',
        'KG' => 'Quirguistão',
        'KI' => 'Quiribáti',
        'GB' => 'Reino Unido',
        'CF' => 'República Centro-Africana',
        'DO' => 'República Dominicana',
        'RO' => 'Roménia',
        'RW' => 'Ruanda',
        'RU' => 'Rússia',
        'WS' => 'Samoa',
        'LC' => 'Santa Lúcia',
        'KN' => 'São Cristóvão e Neves',
        'SM' => 'São Marinho',
        'ST' => 'São Tomé e Príncipe',
        'VC' => 'São Vicente e Granadinas',
        'SC' => 'Seicheles',
        'SN' => 'Senegal',
        'SL' => 'Serra Leoa',
        'RS' => 'Sérvia',
        'SG' => 'Singapura',
        'SY' => 'Síria',
        'SO' => 'Somália',
        'LK' => 'Sri Lanca',
        'SD' => 'Sudão',
        'SS' => 'Sudão do Sul',
        'SE' => 'Suécia',
        'CH' => 'Suíça',
        'SR' => 'Suriname',
        'TH' => 'Tailândia',
        'TW' => 'Taiwan',
        'TJ' => 'Tajiquistão',
        'TZ' => 'Tanzânia',
        'TL' => 'Timor-Leste',
        'TG' => 'Togo',
        'TO' => 'Tonga',
        'TT' => 'Trindade e Tobago',
        'TN' => 'Tunísia',
        'TM' => 'Turcomenistão',
        'TR' => 'Turquia',
        'TV' => 'Tuvalu',
        'UA' => 'Ucrânia',
        'UG' => 'Uganda',
        'UY' => 'Uruguai',
        'UZ' => 'Usbequistão',
        'VU' => 'Vanuatu',
        'VA' => 'Vaticano',
        'VE' => 'Venezuela',
        'VN' => 'Vietname',
        'ZM' => 'Zâmbia',
        'ZW' => 'Zimbabué',
        'INT' => 'Internacional',
    ];

    /**
     * Regista ou atualiza as origens geográficas.
     *
     * O nome `run` permanece em inglês por corresponder ao método
     * convencional dos seeders do Laravel.
     *
     * @since 2.0.0
     */
    public function run(): void
    {
        $agora = now();

        /** @var list<array{
         *     nome: string,
         *     codigo: string,
         *     created_at: mixed,
         *     updated_at: mixed
         * }> $registos
         */
        $registos = [];

        foreach (
            self::ORIGENS_POR_CODIGO as $codigo => $nome
        ) {
            $registos[] = [
                'nome' => $nome,

                'codigo' => $codigo,

                'created_at' => $agora,

                'updated_at' => $agora,
            ];
        }

        DB::table(
            'origens_geograficas',
        )->upsert(
            $registos,
            [
                'codigo',
            ],
            [
                'nome',
                'updated_at',
            ],
        );
    }
}
