CREATE TABLE IF NOT EXISTS companies (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    afm                 VARCHAR(9)   NOT NULL,
    onomasia            VARCHAR(255) NOT NULL DEFAULT '',
    commer_title        VARCHAR(255) NOT NULL DEFAULT '',
    postal_address      VARCHAR(255) NOT NULL DEFAULT '',
    postal_address_no   VARCHAR(20)  NOT NULL DEFAULT '',
    postal_zip_code     VARCHAR(10)  NOT NULL DEFAULT '',
    postal_area_description VARCHAR(255) NOT NULL DEFAULT '',
    doy_descr           VARCHAR(255) NOT NULL DEFAULT '',
    legal_status_descr  VARCHAR(255) NOT NULL DEFAULT '',
    firm_flag_descr     VARCHAR(255) NOT NULL DEFAULT '',
    deactivation_flag_descr VARCHAR(255) NOT NULL DEFAULT '',
    activities_json     JSON         NULL,
    raw_response_json   JSON         NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_companies_afm (afm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
