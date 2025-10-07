<?php

namespace GardenaProxy\Data;

use GardenaProxy\Config;
use GardenaProxy\Data\Models\AbstractDevice;
use GardenaProxy\Data\Models\Controller;
use GardenaProxy\Data\Models\Sensor;
use GardenaProxy\Data\Models\Valve;
use PDO;
use PDOException;

class SQLite
{
    protected PDO $pdo;
    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->connect();
        $this->createTableForDevices([Controller::class, Sensor::class, Valve::class]);
        $this->createCacheTable();
    }

    protected function connect()
    {
        $dbPath = $this->config->get('SQLITE_DIR') . '/data/callbacks.db';
        $dataDir = dirname($dbPath);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        try {
            $this->pdo = new PDO("sqlite:$dbPath");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new \Exception("Connection failed: " . $e->getMessage());
        }
    }

    protected function createCacheTable()
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS cache (key TEXT PRIMARY KEY, value TEXT, expire_at INTEGER)");
    }

    protected function createTableForDevices(array $deviceClasses)
    {
        // merge fields of all device classes
        $fields = [];
        foreach ($deviceClasses as $class) {
            $reflection = new \ReflectionClass($class);
            foreach ($reflection->getProperties() as $property) {
                if ($property->isStatic()) {
                    continue; // skip static properties
                }
                if (!$property->isPublic()) {
                    continue; // skip private properties
                }
                if ($property->getName() === 'id') {
                    continue; // id is primary key, already defined
                }
                $fields[$property->getName()] = 'TEXT'; // Use TEXT for simplicity
                if (in_array($property->getType()?->getName(), ['int', 'float'])) {
                    $fields[$property->getName()] = 'NUMERIC';
                }
            }
        }

        foreach ($fields as $name => $type) {
            $columns[] = "$name $type";
        }
        $columnsSql = implode(", ", $columns);
        $sql = "CREATE TABLE IF NOT EXISTS devices (id TEXT PRIMARY KEY, $columnsSql)";
        $this->pdo->exec($sql);
    }

    public function setCache(string $key, string $value, int $ttl = 3600)
    {
        $expireAt = time() + $ttl;
        $stmt = $this->pdo->prepare("REPLACE INTO cache (key, value, expire_at) VALUES (:key, :value, :expire_at)");
        $stmt->bindValue(':key', $key);
        $stmt->bindValue(':value', $value);
        $stmt->bindValue(':expire_at', $expireAt);
        $stmt->execute();
    }

    public function getCache(string $key, $alternative = null): ?string
    {
        $stmt = $this->pdo->prepare("SELECT value, expire_at FROM cache WHERE key = :key");
        $stmt->bindValue(':key', $key);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if ($row['expire_at'] >= time()) {
                return $row['value'];
            } else {
                // expired
                $this->deleteCache($key);
            }
        }
        return $alternative;
    }

    public function deleteCache(string $key): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM cache WHERE key = :key");
        $stmt->bindValue(':key', $key);
        return $stmt->execute();
    }

    public function saveDevice(AbstractDevice $device)
    {
        $fields = get_object_vars($device);
        $placeholders = array_map(fn($key) => ":$key", array_keys($fields));

        $sql = "REPLACE INTO devices (" . implode(", ", array_keys($fields)) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);

        foreach ($fields as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();
    }

    public function getDeviceById(string $id): ?AbstractDevice
    {
        $stmt = $this->pdo->prepare("SELECT * FROM devices WHERE id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        return $this->wrapRecordToDevice($stmt->fetch(PDO::FETCH_ASSOC));
    }

    public function getDevicesModifiedAfter(int $timestamp)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM devices WHERE timestamp > :timestamp");
        $stmt->bindValue(':timestamp', $timestamp);
        $stmt->execute();

        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->wrapRecordsToDevices($records);
    }

    public function deleteDevicesOlderThan(int $timestamp): int
    {
        $stmt = $this->pdo->prepare("DELETE FROM devices WHERE timestamp < :timestamp");
        $stmt->bindValue(':timestamp', $timestamp);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function getAllDevices(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM devices");
        $stmt->execute();

        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->wrapRecordsToDevices($records);
    }

    protected function wrapRecordsToDevices(array $records): array
    {
        $devices = [];
        foreach ($records as $record) {
            $device = $this->wrapRecordToDevice($record);
            if ($device) {
                $devices[] = $device;
            }
        }
        return $devices;
    }

    protected function wrapRecordToDevice(array $record): ?AbstractDevice
    {
        $device = null;
        $class = match ($record['type'] ?? null) {
            'Controller' => Controller::class,
            'Sensor' => Sensor::class,
            'Valve' => Valve::class,
            default => null,
        };
        if ($class) {
            $device = new $class();
            foreach ($record as $key => $value) {
                if ($key === 'type') {
                    continue; // type is readonly property
                }
                if (property_exists($device, $key)) {
                    $device->$key = is_numeric($value) ? (int) $value : $value;
                }
            }
        }
        return $device;
    }
}
