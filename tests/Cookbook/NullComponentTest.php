<?php
namespace Ratchet\Tests\Cookbook;
use Ratchet\Cookbook\NullComponent;

/**
 * @covers Ratchet\Cookbook\NullComponent
 */
class NullComponentTest extends \PHPUnit_Framework_TestCase {
    public function testGetSubProtocolsReturnsArray() {
        $null = new NullComponent;

        $this->assertInternalType('array', $null->getSubProtocols());
    }
}