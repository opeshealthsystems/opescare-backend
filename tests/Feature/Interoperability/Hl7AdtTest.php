<?php
namespace Tests\Feature\Interoperability;

use App\Services\Interoperability\Hl7AdtParser;
use Tests\TestCase;

class Hl7AdtTest extends TestCase
{
    private string $sampleAdt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sampleAdt =
            "MSH|^~\\&|HOSP|YAOUNDE|OPESCARE||20260101120000||ADT^A01|MSG001|P|2.5\r\n" .
            "EVN|A01|20260101120000\r\n" .
            "PID|1||PAT-001^^^HOSP^MR||Nkemdirim^Chidi||19850312|M|||123 Rue de la Paix^^Yaounde^CM\r\n" .
            "PV1|1|I|WARD-A^BED-12^HOSP";
    }

    public function test_parses_adt_a01_admit(): void
    {
        $parser = new Hl7AdtParser();
        $result = $parser->parse($this->sampleAdt);

        $this->assertEquals('A01', $result['event_type']);
        $this->assertEquals('PAT-001', $result['patient_id']);
        $this->assertEquals('Nkemdirim', $result['family_name']);
        $this->assertEquals('Chidi', $result['given_name']);
        $this->assertEquals('M', $result['gender']);
        $this->assertEquals('19850312', $result['dob']);
    }

    public function test_parses_patient_location(): void
    {
        $parser = new Hl7AdtParser();
        $result = $parser->parse($this->sampleAdt);

        $this->assertEquals('WARD-A', $result['ward']);
        $this->assertEquals('BED-12', $result['bed']);
    }

    public function test_throws_on_invalid_message(): void
    {
        $parser = new Hl7AdtParser();

        $this->expectException(\InvalidArgumentException::class);
        $parser->parse('NOT_AN_HL7_MESSAGE');
    }
}
