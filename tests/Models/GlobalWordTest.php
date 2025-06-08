<?php
namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\GlobalWord;
use App\Core\Database;
use PDO;

class GlobalWordTest extends TestCase
{
    private ?PDO $pdo = null; // Make nullable for safety
    private GlobalWord $globalWordModel;

    protected function setUp(): void
    {
        Database::resetInstance();
        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->assertNotNull($this->pdo, "PDO connection must be established in setUp.");

        $this->pdo->exec("DELETE FROM learning_set_words"); // Clear dependent tables first
        $this->pdo->exec("DELETE FROM user_leitner_progress");
        $this->pdo->exec("DELETE FROM global_word_bank");

        $this->globalWordModel = new GlobalWord();
    }

    public function testCreateGlobalWord()
    {
        $data = [
            'german_word' => 'Apfel', 'translation' => 'سیب',
            'persian_phonetic_pronunciation' => 'آپفِل', 'word_type' => 'اسم',
            'word_gender' => 'مذکر', 'word_level' => 'A1',
            'example_german' => 'Der Apfel ist rot.', 'example_persian_translation' => 'سیب قرمز است.',
            'audio_url' => 'http://example.com/apfel.mp3'
        ];
        $wordId = $this->globalWordModel->create($data);
        $this->assertNotFalse($wordId, "GlobalWord creation should return an ID.");
        $this->assertIsInt($wordId);

        $word = $this->globalWordModel->findById($wordId);
        $this->assertNotNull($word);
        $this->assertEquals($data['german_word'], $word['german_word']);
        $this->assertEquals($data['word_level'], $word['word_level']);
        $this->assertEquals($data['audio_url'], $word['audio_url']);
    }

    public function testCreateGlobalWordWithMinimumFields()
    {
        $data = [
            'german_word' => 'Banane', 'translation' => 'موز',
            // All other fields are nullable and should default to null in the model/DB
            'persian_phonetic_pronunciation' => null, 'word_type' => null,
            'word_gender' => null, 'word_level' => null,
            'example_german' => null, 'example_persian_translation' => null,
            'audio_url' => null
        ];
        $wordId = $this->globalWordModel->create($data);
        $this->assertNotFalse($wordId);
        $word = $this->globalWordModel->findById($wordId);
        $this->assertNotNull($word);
        $this->assertEquals($data['german_word'], $word['german_word']);
        $this->assertNull($word['word_level']);
        $this->assertNull($word['audio_url']);
    }

    public function testFindByGermanWord()
    {
        // Provide all required fields for create, even if some are null in this specific test case
        $data = [
            'german_word' => 'Kirsche', 'translation' => 'گیلاس',
            'persian_phonetic_pronunciation' => null, 'word_type' => null,
            'word_gender' => null, 'word_level' => null,
            'example_german' => null, 'example_persian_translation' => null,
            'audio_url' => null
        ];
        $this->globalWordModel->create($data);
        $word = $this->globalWordModel->findByGermanWord('Kirsche');
        $this->assertNotNull($word);
        $this->assertEquals('گیلاس', $word['translation']);
        $this->assertNull($this->globalWordModel->findByGermanWord('NichtExistent'));
    }

    public function testUpdateGlobalWord()
    {
        $data = [
            'german_word' => 'Pflaume', 'translation' => 'آلو', 'word_level' => 'A1',
            'persian_phonetic_pronunciation' => null, 'word_type' => null, 'word_gender' => null,
            'example_german' => null, 'example_persian_translation' => null, 'audio_url' => null
        ];
        $wordId = $this->globalWordModel->create($data);
        $this->assertNotFalse($wordId);

        $updateData = [
            'german_word' => 'Pflaume', 'translation' => 'آلو بخارا',
            'persian_phonetic_pronunciation' => 'فلاومِه', 'word_type' => 'اسم',
            'word_gender' => 'مونث', 'word_level' => 'A2',
            'example_german' => 'Die Pflaume ist süß.', 'example_persian_translation' => 'آلو شیرین است.',
            'audio_url' => 'http://example.com/pflaume.mp3'
        ];
        $result = $this->globalWordModel->update($wordId, $updateData);
        $this->assertTrue($result);

        $updatedWord = $this->globalWordModel->findById($wordId);
        $this->assertNotNull($updatedWord);
        $this->assertEquals('آلو بخارا', $updatedWord['translation']);
        $this->assertEquals('A2', $updatedWord['word_level']);
        $this->assertEquals('فلاومِه', $updatedWord['persian_phonetic_pronunciation']);
    }

    public function testDeleteGlobalWord()
    {
        $data = [
            'german_word' => 'Erdbeere', 'translation' => 'توت فرنگی',
            'persian_phonetic_pronunciation' => null, 'word_type' => null, 'word_gender' => null,
            'word_level' => null, 'example_german' => null, 'example_persian_translation' => null,
            'audio_url' => null
        ];
        $wordId = $this->globalWordModel->create($data);
        $this->assertNotFalse($wordId);

        $result = $this->globalWordModel->delete($wordId);
        $this->assertTrue($result);
        $this->assertNull($this->globalWordModel->findById($wordId));
    }

    public function testGetAllGlobalWordsAndCount()
    {
        $this->assertEquals(0, $this->globalWordModel->countAll());
        $this->globalWordModel->create(['german_word' => 'Zitrone', 'translation' => 'لیمو', /* other fields null */]);
        $this->globalWordModel->create(['german_word' => 'Orange', 'translation' => 'پرتقال', /* other fields null */]);

        $this->assertEquals(2, $this->globalWordModel->countAll());
        $words = $this->globalWordModel->getAll(10, 0, 'german_word', 'ASC');
        $this->assertCount(2, $words);
        $this->assertEquals('Orange', $words[0]['german_word']);
        $this->assertEquals('Zitrone', $words[1]['german_word']);
    }

    protected function tearDown(): void
    {
        Database::resetInstance();
        $this->pdo = null;
    }
}
