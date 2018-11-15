<?php

class CountryUpdate
{
    public $db = null;
    private $apiKey = "fd816de161de06cc0adcd835587b1ea9";

    private function getDbConnection()
    {
        try {
            $this->db = new PDO(
                'mysql:host=localhost;dbname=ip',
                'root',
                '');

            $this->db->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            echo "Ошибка" . $e->getMessage() . "</br>";
        }

    }

    public function __construct()
    {
        $this->getDbConnection();
    }

    public function getIpFromDb()
    {
        $res = $this->db->query('SELECT ip FROM tb_ip_addresses');
        $res = $res->fetchAll(PDO::FETCH_ASSOC);

        return $res;
    }

    public function getCountryToIp()
    {
        $ipsArr = $this->getIpFromDb();
        $countryToIp = array();
        foreach ($ipsArr as $ip) {
            $ipApi = curl_init(
                'http://api.ipapi.com/' .
                $ip['ip'] .
                '?access_key=' . $this->apiKey);

            curl_setopt($ipApi, CURLOPT_RETURNTRANSFER, true);

            $ipData = curl_exec($ipApi);

            if ($ipData === false) {
                echo 'Не удалось подключиться к ресурсу: ' . curl_error($ipApi);
                curl_close($ipApi);
            } else {
                $ipData = json_decode($ipData, true);
                array_push(
                    $countryToIp,
                    array(
                        'ip' => $ip['ip'],
                        'country' => $ipData['country_name'])
                );
            }
        }

        return $countryToIp;
    }

    public function insertCountriesToIp()
    {
        $countryToIp = $this->getCountryToIp();
        $sql = 'UPDATE tb_ip_addresses SET country = :country WHERE ip = :ip';
        $query = $this->db->prepare($sql);
        $this->db->beginTransaction();

        foreach ($countryToIp as $data) {
            try {
                $query->bindParam(':country', $data['country']);
                $query->bindParam(':ip', $data['ip']);
                $query->execute();
            } catch (Exception $e) {
                $this->db->rollBack();
                echo "Ошибка: " . $e->getMessage();
            }
        }
        $this->db->commit();
    }

}


$OBJ = new  CountryUpdate();

$OBJ->insertCountriesToIp();


