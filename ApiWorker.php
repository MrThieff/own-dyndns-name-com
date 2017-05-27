<?php

class ApiWorker
{
    /** @var CurlClient */
    private $curlClient;

    /** @var DnsRecord */
    private $dnsRecord;

    /** @var array */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->curlClient = new CurlClient($config);
        $this->dnsRecord = new DnsRecord($config['domain_name_prefix']);

        $this->curlClient->setAuthHeader($this->getAuthHeader());
        $currentIP = $this->getCurrentIP();
        $this->dnsRecord->setContent($currentIP);
        consoleLog("Current IP is $currentIP");
    }

    /**
     * @return string
     * @throws MyException
     */
    public function getAuthHeader(): string
    {
        $loginData = [
            'username'  => $this->config['account'],
            'api_token' => $this->config['token']
        ];

        $response = $this->curlClient->getResponse('/api/login', false, true, $loginData);
        if (empty($response['session_token'])) {
            throw new MyException("Can't get Session Token");
        }

        return 'Api-Session=Token: ' . $response['session_token'];
    }

    /**
     * @return string
     * @throws MyException
     */
    public function getCurrentIP(): string
    {
        $response = $this->curlClient->getResponse('/api/hello');
        if (empty($response['client_ip'])) {
            throw new MyException("Can't get my IP.");
        }

        return $response['client_ip'];
    }

    public function getDomains()
    {
        //GET /api/domain/list
    }

    /**
     * @param string|null $domainName
     * @return array
     * @throws MyException
     */
    public function getDnsRecords(string $domainName = null): array
    {
        $domainName = empty($domainName) ? $this->config['domain'] : $domainName;

        $response = $this->curlClient->getResponse('/api/dns/list/' . $domainName, true, false, []);
        if (empty($response['records']) || !is_array($response['records'])) {
            throw new MyException("Can't get list of DNS records.");
        }

        return $response['records'];
    }

    /**
     * Return DNS record ID. Can be returned empty string if record not found.
     *
     * @param string|null $domainName
     * @param string|null $subDomainPrefix
     * @return string
     * @throws MyException
     */
    public function getDnsRecordId(string $domainName = null, string $subDomainPrefix = null): string
    {
        $domainName = empty($domainName) ? $this->config['domain'] : $domainName;
        $subDomainPrefix = empty($subDomainPrefix) ? $this->config['domain_name_prefix'] : $subDomainPrefix;

        $dnsRecords = $this->getDnsRecords($domainName);

        $recordId = '';
        $subDomainPrefix = str_replace('.', '\.', $subDomainPrefix);
        $domainName = str_replace('.', '\.', $domainName);
        $pattern = '/^' . $subDomainPrefix . '\.' . $domainName . '$/';
        foreach ($dnsRecords as $record) {
            if (preg_match($pattern, $record['name'])) { //TODO normal validation
                $recordId = $record['record_id'];
                break;
            }
        }

        return $recordId;
    }

    /**
     * @param string|null $domainName
     * @throws MyException
     */
    public function createDnsRecord(string $domainName = null)
    {
        $domainName = empty($domainName) ? $this->config['domain'] : $domainName;

        $dnsRecord = $this->dnsRecord->getRecord();

        $response = $this->curlClient->getResponse('/api/dns/create/' . $domainName, true, true, $dnsRecord);
        if (empty($response)) {
            throw new MyException("Can't create DNS record " . json_encode($dnsRecord) . " for Domain: $domainName");
        }
    }

    /**
     * @param string $recordId
     * @param string|null $domainName
     * @throws MyException
     */
    public function deleteDnsRecord(string $recordId, string $domainName = null)
    {
        $domainName = empty($domainName) ? $this->config['domain'] : $domainName;

        $data = ['record_id' => $recordId];
        $response = $this->curlClient->getResponse('/api/dns/delete/' . $domainName, true, true, $data);
        if (empty($response)) {
            throw new MyException("Can't delete DNS record #$recordId for Domain: $domainName");
        }
    }

    /**
     * @throws MyException
     */
    public function updateDnsRecord()
    {
        $dnsRecord = $this->dnsRecord->getRecord();
        $recordId = $this->getDnsRecordId();
        if (empty($recordId)) {
            consoleLog('DNS record "' . $this->config['domain_name_prefix'] . $this->config['domain'] . '"', 'not found');
        } else {
            $this->deleteDnsRecord($recordId);
            consoleLog("DNS record #$recordId \"" . $this->config['domain_name_prefix'] . '"', 'deleted');
        }

        $this->createDnsRecord();
        consoleLog("DNS Record " . json_encode($dnsRecord), "added");
    }
}