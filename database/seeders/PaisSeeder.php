<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Geografia\Pais;
use Illuminate\Database\Seeder;

/**
 * Regista os países, territórios especiais e agrupamentos geográficos
 * utilizados pela aplicação.
 *
 * O nome `Seeder` permanece em inglês por corresponder à convenção utilizada
 * pelo Laravel.
 *
 * Os seguintes códigos pertencem ao intervalo reservado para utilização
 * interna da aplicação e não representam códigos ISO 3166-1 oficialmente
 * atribuídos:
 *
 * - `XE`: Inglaterra;
 * - `XK`: Kosovo;
 * - `XN`: Irlanda do Norte;
 * - `XS`: Escócia;
 * - `XW`: País de Gales;
 * - `XX`: Internacional.
 *
 * O Reino Unido permanece disponível separadamente através do código oficial
 * `GB`.
 *
 * Caso a coluna de códigos passe futuramente a aceitar mais de dois
 * caracteres, as nações constituintes do Reino Unido poderão utilizar os
 * códigos alargados `GB-ENG`, `GB-NIR`, `GB-SCT` e `GB-WLS`.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
final class PaisSeeder extends Seeder
{
    /**
     * Países e entidades geográficas indexados pelo respetivo código.
     *
     * @var array<string, string>
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private const PAISES_POR_CODIGO = [
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
        'XS' => 'Escócia',
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
        'XE' => 'Inglaterra',
        'IR' => 'Irão',
        'IQ' => 'Iraque',
        'IE' => 'Irlanda',
        'XN' => 'Irlanda do Norte',
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
        'XW' => 'País de Gales',
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
        'ZW' => 'Zimbábue',
        'XX' => 'Internacional',
    ];

    /**
     * Regista ou atualiza os países e entidades geográficas da aplicação.
     *
     * O nome `run` permanece em inglês por corresponder ao método
     * convencional dos seeders do Laravel.
     *
     * Os registos são inseridos através de uma única operação `upsert`,
     * tornando o seeder idempotente e evitando uma consulta individual por
     * registo.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function run(): void
    {
        $agora =
            now();

        /** @var list<array{
         *     nome: string,
         *     codigo_iso: string,
         *     created_at: mixed,
         *     updated_at: mixed
         * }> $registos
         */
        $registos = [];

        foreach (
            self::PAISES_POR_CODIGO as $codigoIso => $nome
        ) {
            $registos[] = [
                'nome' => $nome,

                'codigo_iso' => $codigoIso,

                'created_at' => $agora,

                'updated_at' => $agora,
            ];
        }

        Pais::query()->upsert(
            $registos,
            [
                'codigo_iso',
            ],
            [
                'nome',
                'updated_at',
            ],
        );
    }
}
