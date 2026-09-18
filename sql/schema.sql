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

CREATE TABLE IF NOT EXISTS novus_requests (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_afm            VARCHAR(9)  NOT NULL,
    novus_request_id       VARCHAR(64) NOT NULL,
    request_type           VARCHAR(20) NOT NULL DEFAULT '',
    status                 VARCHAR(30) NOT NULL DEFAULT '',
    contract_number        VARCHAR(50) NULL,
    contract_date          DATE NULL,
    template_version       VARCHAR(20) NULL,
    provisioning_status    VARCHAR(20) NULL,
    aade_statement_status  VARCHAR(30) NULL,
    idempotency_key        VARCHAR(64) NULL,
    raw_json               JSON NULL,
    created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_novus_request_id (novus_request_id),
    KEY idx_company_afm (company_afm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS novus_webhook_events (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id            VARCHAR(64)  NOT NULL,
    event_type          VARCHAR(50)  NOT NULL DEFAULT '',
    novus_request_id    VARCHAR(64)  NULL,
    company_afm         VARCHAR(9)   NULL,
    occurred_at         DATETIME     NULL,
    signature_valid     TINYINT(1)   NOT NULL DEFAULT 0,
    email_sent          TINYINT(1)   NOT NULL DEFAULT 0,
    payload_json        JSON NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_event_id (event_id),
    KEY idx_novus_request_id (novus_request_id),
    KEY idx_company_afm (company_afm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
