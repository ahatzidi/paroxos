<?php

namespace Paroxos\Aade;

use PDO;

class CompanyRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function findByAfm(string $afm): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM companies WHERE afm = :afm LIMIT 1');
        $stmt->execute(['afm' => $afm]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function upsert(array $data, array $raw): void
    {
        $sql = 'INSERT INTO companies (
                    afm, onomasia, commer_title, postal_address, postal_address_no,
                    postal_zip_code, postal_area_description, doy_descr,
                    legal_status_descr, firm_flag_descr, deactivation_flag_descr,
                    activities_json, raw_response_json
                ) VALUES (
                    :afm, :onomasia, :commer_title, :postal_address, :postal_address_no,
                    :postal_zip_code, :postal_area_description, :doy_descr,
                    :legal_status_descr, :firm_flag_descr, :deactivation_flag_descr,
                    :activities_json, :raw_response_json
                )
                ON DUPLICATE KEY UPDATE
                    onomasia = VALUES(onomasia),
                    commer_title = VALUES(commer_title),
                    postal_address = VALUES(postal_address),
                    postal_address_no = VALUES(postal_address_no),
                    postal_zip_code = VALUES(postal_zip_code),
                    postal_area_description = VALUES(postal_area_description),
                    doy_descr = VALUES(doy_descr),
                    legal_status_descr = VALUES(legal_status_descr),
                    firm_flag_descr = VALUES(firm_flag_descr),
                    deactivation_flag_descr = VALUES(deactivation_flag_descr),
                    activities_json = VALUES(activities_json),
                    raw_response_json = VALUES(raw_response_json)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'afm' => $data['afm'],
            'onomasia' => $data['onomasia'],
            'commer_title' => $data['commer_title'],
            'postal_address' => $data['postal_address'],
            'postal_address_no' => $data['postal_address_no'],
            'postal_zip_code' => $data['postal_zip_code'],
            'postal_area_description' => $data['postal_area_description'],
            'doy_descr' => $data['doy_descr'],
            'legal_status_descr' => $data['legal_status_descr'],
            'firm_flag_descr' => $data['firm_flag_descr'],
            'deactivation_flag_descr' => $data['deactivation_flag_descr'],
            'activities_json' => json_encode($data['activities'], JSON_UNESCAPED_UNICODE),
            'raw_response_json' => json_encode($raw, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
