<?php
/**
 * Test cases for URN and check digit generation.
 *
 * @category    Tests
 * @package     Opus_Identifier
 *
 * @group       UrnTest
 */
class Opus_Identifier_UrnTest extends PHPUnit_Framework_TestCase {

    /**
     * Test data provider
     *
     * @return array Array containing document identifier, URN and check digit pairs.
     */
    public function provider() {
        return array(
        array('8765' , 'urn:nbn:de:swb:14-opus-8765' , '0'),
        array('1913' , 'urn:nbn:de:swb:14-opus-1913' , '1'),
        array('6543' , 'urn:nbn:de:swb:14-opus-6543' , '2'),
        array('1234' , 'urn:nbn:de:swb:14-opus-1234' , '3'),
        array('7000' , 'urn:nbn:de:swb:14-opus-7000' , '4'),
        array('4567' , 'urn:nbn:de:swb:14-opus-4567' , '5'),
        array('4028' , 'urn:nbn:de:swb:14-opus-4028' , '6'),
        array('3456' , 'urn:nbn:de:swb:14-opus-3456' , '7'),
        array('4711' , 'urn:nbn:de:swb:14-opus-4711' , '8'),
        array('2345' , 'urn:nbn:de:swb:14-opus-2345' , '9'),
        );
    }

    /**
     * Test data provider for bad input values.
     *
     * @return array Array containing invalid namespace identifier pairs.
     */
    public function badProvider() {
        return array(
        array('!ERROR!', '14', 'opus', 'Used invalid first subnamespace identifier.'),
        array('swb', '!ERROR!', 'opus', 'Used invalid second subnamespace identifier.'),
        array('swb', '14', '!ERROR!', 'Used invalid namespace specific string.')
        );
    }

    /**
     * Test data provider for a bad document identifier input value.
     *
     * @return array Array containing invalid document identifier.
     */
    public function badIdProvider() {
        return array(
        array('!ERROR!', 'Used invalid arguments for document id.'),
        );
    }

    /**
     * Test if a valid URN is generated.
     *
     * @param string $document_id Identifier of the Document.
     * @param string $urn         A full qualified and valid URN.
     * @param string $checkdigit  Check digit valid for the given URN.
     * @return void
     *
     * @dataProvider provider
     */
    public function testUrn($document_id, $urn, $checkdigit) {
        $identifier = new Opus_Identifier_Urn('swb', '14', 'opus');
        $generated = $identifier->getUrn($document_id);
        $this->assertEquals($urn . $checkdigit, $generated, 'Generated URN is not valid.');
    }

    /**
     * Test if a valid check digit is generated
     *
     * @param string $document_id Identifier of the Document.
     * @param string $urn         A full qualified and valid URN.
     * @param string $checkdigit  Check digit valid for the given URN.
     * @return void
     *
     * @dataProvider provider
     */
    public function testCheckDigit($document_id, $urn, $checkdigit) {
        $identifier = new Opus_Identifier_Urn('swb', '14', 'opus');
        $generated = $identifier->getCheckDigit($document_id);
        $this->assertEquals($checkdigit, $generated, 'Generated check digit is not valid.');
    }

    /**
     * Test if illegal identifier values raise exceptions.
     *
     * @param string $snid1 First subnamespace identifier part of the URN.
     * @param string $snid2 Second subnamespace identifier part of the URN.
     * @param string $niss  Namespace specific string part of the URN.
     * @param string $msg   Message on failing test.
     * @return void
     *
     * @dataProvider badProvider
     */
    public function testInitializeWithInvalidValues($snid1, $snid2, $niss, $msg) {
        $this->setExpectedException('InvalidArgumentException', $msg);
        $identifier = new Opus_Identifier_Urn($snid1, $snid2, $niss);
    }

    /**
     * Test if illegal document identifier value raises an exception on calling urn generator.
     *
     * @param string $document_id Identifier of the Document.
     * @param string $msg         Message on failing test.
     * @return void
     *
     * @dataProvider badIdProvider
     */
    public function testCallUrnGeneratorWithInvalidValue($document_id, $msg) {
        $this->setExpectedException('InvalidArgumentException', $msg);
        $identifier = new Opus_Identifier_Urn('swb', '14', 'opus');
        $generated = $identifier->getUrn($document_id);
    }

    /**
     * Test if illegal document identifier value raises an exception on calling check digit generator.
     *
     * @param string $document_id Identifier of the Document.
     * @param string $msg         Message on failing test.
     * @return void
     *
     * @dataProvider badIdProvider
     */
    public function testCallCheckDigitGeneratorWithInvalidValue($document_id, $msg) {
        $this->setExpectedException('InvalidArgumentException', $msg);
        $identifier = new Opus_Identifier_Urn('swb', '14', 'opus');
        $generated = $identifier->getCheckDigit($document_id);
    }
}