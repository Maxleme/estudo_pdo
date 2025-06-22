<?php 

use Alura\Pdo\Domain\Model\Student;
use Alura\Pdo\Infrastructure\Repository\PdoStudentRepository;

require_once 'vendor/autoload.php';

$connection = \Alura\Pdo\Infrastructure\Persistence\ConnectionCreator::createConnection();
$studantRepository = new PdoStudentRepository($connection);

$connection->beginTransaction();
try {
    $firstStudent = new Student(
        null,
        'João Primeiro',
        new DateTimeImmutable('1995-05-12')
    );
    $studantRepository->save($firstStudent);

    $secondStudent =    new Student(
        null,
        'João Segundo',
        new DateTimeImmutable('1994-01-11')
    );
    $studantRepository->save($secondStudent);

    $connection->commit();
} catch (\PDOException $e) {
    echo $e->getMessage();
    $connection->rollBack();
}