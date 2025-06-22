<?php

namespace Alura\Pdo\Infrastructure\Repository;

use Alura\Pdo\Domain\Repository\StudentRepository;
use Alura\Pdo\Domain\Model\Student;
use PDO;
class PdoStudentRepository implements StudentRepository
{   
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }
    public function allStudents(): array
    {
        $statement = $this->connection->query('SELECT * FROM students;');

        return $this->hydrateStudentList($statement);
    }

    public function studentsBirthAt(\DateTimeInterface $birthDate): array
    {
        $statement = $this->connection->query('SELECT * FROM students WHERE birth_date = ?;');
        $statement->bindValue(1, $birthDate->format('Y-m-d'));
        $statement->execute();

        return $this->hydrateStudentList($statement);
    }

    public function hydrateStudentList(\PDOStatement $statement): array 
    {
        $studentsDataList = $statement->fetchAll();
        $studentsList = [];

        foreach( $studentsDataList as $studentData   ) {
            $studentsList = new Student(
                $studentData['id'],
                $studentData['name'],
                new \DateTimeImmutable($studentData['birt_date']),
            );
        }
        return $studentsList;
    }

    public function save(Student $student): bool
    {
        if($student->id() === null) {
            return $this->insert($student);
        }

        return $this->update($student);
    }

    public function update(Student $student): bool
    {
        $sqlUpdate = "UPDATE INTO students (name, birth_date) VALUES (:name, :birth_date) WHERE id = :id;";
        $statement = $this->connection->prepare($sqlUpdate);
        $statement->bindValue(':name', $student->name());
        $statement->bindValue(':birth_date', $student->birthDate()->format('Y-m-d'));
        $statement->bindValue(':id', $student->id(), PDO::PARAM_INT);

        return $statement->execute();
    }

    public function insert(Student $student): bool
    {   
        $sqlInsert = "INSERT INTO students (name, birth_date) VALUES (:name, :birth_date);";
        $statement = $this->connection->prepare($sqlInsert);
        
        $succcess = $statement->execute([
            ':name' => $student->name(),
            ':birth_date' => $student->birthDate()->format('Y-m-d'),
        ]);
        
        if($succcess){
            $student->defineId($this->connection->lastInsertId());   
        }
        
        return $succcess;

    }
    public function remove(Student $student): bool
    {
        $preparedStatement = $this->connection->prepare('DELETE FROM students WHERE id = ?;');
        $preparedStatement->bindValue(1, $student->id(), PDO::PARAM_INT);
        
        return $preparedStatement->execute();
    }
}