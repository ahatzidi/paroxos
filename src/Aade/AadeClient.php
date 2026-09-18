<?php

namespace Paroxos\Aade;

use RuntimeException;
use SoapClient;
use SoapFault;
use SoapHeader;
use SoapVar;

/**
 * Client για το δημόσιο webservice Μητρώου της ΑΑΔΕ (RgWsPublic2).
 * Εγγραφή/ενεργοποίηση πρόσβασης: https://www1.aade.gr/webtax/wspublicreg/wspublicreg.php
 */
class AadeClient
{
    private const WSSE_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

    private SoapClient $client;
    private string $afmCalledBy;

    public function __construct(array $config)
    {
        foreach (['wsdl', 'username', 'password', 'afm_called_by'] as $key) {
            if (empty($config[$key])) {
                throw new RuntimeException("Λείπει η ρύθμιση AADE '{$key}' στο config.php");
            }
        }

        $this->afmCalledBy = $config['afm_called_by'];

        $this->client = new SoapClient($config['wsdl'], [
            'trace' => true,
            'exceptions' => true,
            'connection_timeout' => 15,
            'cache_wsdl' => WSDL_CACHE_MEMORY,
        ]);

        $this->client->__setSoapHeaders([$this->buildWsseHeader($config['username'], $config['password'])]);
    }

    private function buildWsseHeader(string $username, string $password): SoapHeader
    {
        $xml = sprintf(
            '<wsse:Security xmlns:wsse="%s" soap:mustUnderstand="1"
                xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                <wsse:UsernameToken>
                    <wsse:Username>%s</wsse:Username>
                    <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">%s</wsse:Password>
                </wsse:UsernameToken>
            </wsse:Security>',
            self::WSSE_NS,
            htmlspecialchars($username, ENT_XML1),
            htmlspecialchars($password, ENT_XML1)
        );

        $var = new SoapVar($xml, XSD_ANYXML);

        return new SoapHeader(self::WSSE_NS, 'Security', $var, true);
    }

    /**
     * Αναζήτηση στοιχείων επιχείρησης βάσει ΑΦΜ.
     *
     * @return array{ok: bool, error: ?string, data: ?array, raw: ?array}
     */
    public function searchByAfm(string $afm): array
    {
        $afm = preg_replace('/\D/', '', $afm);

        if (strlen($afm) !== 9) {
            return ['ok' => false, 'error' => 'Το ΑΦΜ πρέπει να αποτελείται από 9 ψηφία.', 'data' => null, 'raw' => null];
        }

        try {
            $response = $this->client->rgWsPublicAfmMethod([
                'INPUT_REC' => [
                    'afm_called_by' => $this->afmCalledBy,
                    'afm_called_for' => $afm,
                ],
            ]);
        } catch (SoapFault $e) {
            return ['ok' => false, 'error' => 'Σφάλμα επικοινωνίας με την ΑΑΔΕ: ' . $e->getMessage(), 'data' => null, 'raw' => null];
        }

        $raw = json_decode(json_encode($response), true);

        $errorRec = $raw['rg_ws_public_afm_method_result']['error_rec'] ?? null;
        if (!empty($errorRec) && !empty($errorRec['error_descr'])) {
            return ['ok' => false, 'error' => $errorRec['error_descr'], 'data' => null, 'raw' => $raw];
        }

        $basic = $raw['rg_ws_public_afm_method_result']['basic_rt'] ?? null;
        if (empty($basic)) {
            return ['ok' => false, 'error' => 'Δεν βρέθηκαν στοιχεία για το ΑΦΜ ' . $afm, 'data' => null, 'raw' => $raw];
        }

        $activities = [];
        $firmActTab = $raw['rg_ws_public_afm_method_result']['firm_act_tab']['item'] ?? [];
        if (isset($firmActTab['firm_act_descr'])) {
            $firmActTab = [$firmActTab];
        }
        foreach ($firmActTab as $act) {
            if (!empty($act['firm_act_descr'])) {
                $activities[] = [
                    'code' => $act['firm_act_code'] ?? null,
                    'description' => $act['firm_act_descr'],
                    'is_main' => ($act['firm_act_kind_code'] ?? null) === '1',
                ];
            }
        }

        $data = [
            'afm' => $basic['afm'] ?? $afm,
            'onomasia' => $basic['onomasia'] ?? '',
            'commer_title' => $basic['commer_title'] ?? '',
            'postal_address' => $basic['postal_address'] ?? '',
            'postal_address_no' => $basic['postal_address_no'] ?? '',
            'postal_zip_code' => $basic['postal_zip_code'] ?? '',
            'postal_area_description' => $basic['postal_area_description'] ?? '',
            'doy_descr' => $basic['doy_descr'] ?? '',
            'legal_status_descr' => $basic['legal_status_descr'] ?? '',
            'firm_flag_descr' => $basic['firm_flag_descr'] ?? '',
            'deactivation_flag_descr' => $basic['deactivation_flag_descr'] ?? '',
            'activities' => $activities,
        ];

        return ['ok' => true, 'error' => null, 'data' => $data, 'raw' => $raw];
    }
}
