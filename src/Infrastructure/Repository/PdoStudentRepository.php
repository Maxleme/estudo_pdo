<?php

namespace Alura\Pdo\Infrastructure\Repository;

use Alura\Pdo\Domain\Repository\StudentRepository;
use Alura\Pdo\Domain\Model\Student;
use PDO;
use Alura\Pdo\Domain\Model\Phone;
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
            $studentList = new Student(
                $studentData['id'],
                $studentData['name'],
                new \DateTimeImmutable($studentData['birth_date'])
            );

        }
        return $studentsList;
    }

    //DEIXEI O METODO APENAS PARA EFEITOS DE ESTUDO, MAS ELE NÃO ESTA MAIS SENDO UTILIZADO

    // private function fillPhonesOf(Student $student): void
    // {
    //     $sqlQuery = 'SELECT id, area_code, number FROM phones WHERE student_id = ?';
    //     $statement = $this->connection->prepare($sqlQuery);
    //     $statement->bindValue(1, $student->id(), PDO::PARAM_INT);
    //     $statement->execute();

    //     $phoneDataList = $statement->fetchAll();
    //     foreach( $phoneDataList as $phoneData ) {
    //         $phone = new Phone(
    //             $phoneData['id'],
    //             $phoneData['area_code'],
    //             $phoneData['number']
    //         );
    //         $student->addPhone($phone);
    //     }

    // }

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

    public function studentsWithPhones(): array
    {
        $sqlQuery = 'SELECT students.id, students.name, students.birth_date, phones.id AS phone_id, phones.area_code, phones.number FROM students JOIN phones ON students.id = phones.student_id;';
        $stmt = $this->connection->prepare($sqlQuery);
        $result = $stmt->fetchAll();
        $studentList = [];

        foreach ($result as $row) {
            if(!array_key_exists($row['id'], $studentList)) {
                $studentList[$row['id']] =  new Student(
                    $row['id'],
                    $row['name'],
                    new \DateTimeImmutable($row['birth_date'])
                );      
            }
            $phone = new Phone($row['phone_id'], $row['area_code'], $row['number']);
            $studentList[$row['id']]->addPhone($phone);
        }
        return $studentList;
    }
}