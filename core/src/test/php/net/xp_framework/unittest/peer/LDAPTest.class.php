<?php namespace net\xp_framework\unittest\peer;
 
use peer\ldap\LDAPClient;
use peer\ldap\LDAPQuery;
use unittest\TestCase;


/**
 * Test LDAP client
 *
 * @see      xp://peer.ldap.LDAPClient
 * @purpose  Unit Test
 */
class LDAPTest extends TestCase {
  protected
    $lc= null;

  protected static
    $previouslyFailed = false;

  /**
   * Setup method
   *
   */
  public function setUp() {
    if (self::$previouslyFailed) {
      throw new \unittest\PrerequisitesNotMetError('Previously failed to set up.');
    }
    
    if (!extension_loaded('ldap')) {
      throw new \unittest\PrerequisitesNotMetError('LDAP extension not available.');
    }
    
    $this->lc= new LDAPClient('ldap.openldap.org');
    try {
      $this->lc->setOption(LDAP_OPT_PROTOCOL_VERSION, 3);
      $this->lc->connect();
      $this->lc->bind();
    } catch (\peer\ConnectException $e) {
      self::$previouslyFailed= true;
      throw new \unittest\PrerequisitesNotMetError(
        PREREQUISITE_INITFAILED,
        $e,
        array('connect', 'ldapv3://ldap.openldap.org')
      );
    } catch (\peer\ldap\LDAPException $e) {
      self::$previouslyFailed= true;
      throw new \unittest\PrerequisitesNotMetError(
        PREREQUISITE_INITFAILED,
        $e,
        array('bind', 'ldapv3://ldap.openldap.org')
      );
    }
  }
  
  /**
   * Tear down this test case.
   *
   */
  public function tearDown() {
    $this->lc->close();
  }
  
  /**
   * Test LDAP search
   *
   */
  #[@test]
  public function search() {
    $res= $this->lc->search(
      'ou=People,dc=OpenLDAP,dc=Org', 
      '(objectClass=*)'
    );
    $this->assertClass($res, 'peer.ldap.LDAPSearchResult');
    $this->assertNotEquals(0, $res->numEntries());
    $entry= $res->getFirstEntry();
    $this->assertClass($entry, 'peer.ldap.LDAPEntry');
  }
  
  /**
   * Test LDAP read
   *
   * @param   
   * @return  
   */
  #[@test]
  public function readEntry() {
    $res= $this->lc->read(new \peer\ldap\LDAPEntry('uid=kurt,ou=People,dc=OpenLDAP,dc=Org'));
    $this->assertEquals('uid=kurt,ou=People,dc=OpenLDAP,dc=Org', $res->getDN());
  }
  
  /**
   * Test LDAP read on non-existing object
   *
   */
  #[@test, @expect('peer.ldap.LDAPException')]
  public function readNonExistingEntry() {
    $this->assertEquals(null, $this->lc->read(new \peer\ldap\LDAPEntry('uid=unknown,ou=People,dc=OpenLDAP,dc=Org')));
  }
  
  /**
   * Test LDAP search with result limit of n (here: three)
   *
   */
  #[@test]
  public function searchWithSizeLimitN() {
    with ($query= new LDAPQuery('ou=People,dc=OpenLDAP,dc=Org', '(objectClass=*)'), $limit= 3); {
      $query->setSizelimit($limit);
      $query->setScope(LDAP_SCOPE_SUB);
      $res= $this->lc->searchBy($query);
    
      $this->assertClass($res, 'peer.ldap.LDAPSearchResult');
      $this->assertNotEquals(0, $res->numEntries());
      $this->assertClass($res->getFirstEntry(), 'peer.ldap.LDAPEntry');
    
      $i= 0;
      while ($res->getNextEntry()) { 
        $i++;
      }
      $this->assertTrue($i > 1, 'At least one entry, have '.$i);
      $this->assertTrue($i <= $limit, 'At most '.$limit.' entries, have '.$i);
    }
  }

  /**
   * Test LDAP search with result limit of 1
   *
   */
  #[@test]
  public function searchWithSizeLimitOne() {
    with ($query= new LDAPQuery('ou=People,dc=OpenLDAP,dc=Org', '(objectClass=*)')); {
      $query->setSizelimit(1);
      $query->setScope(LDAP_SCOPE_SUB);
      $res= $this->lc->searchBy($query);
    
      $this->assertClass($res, 'peer.ldap.LDAPSearchResult');
      $this->assertEquals(1, $res->numEntries());
      $this->assertClass($res->getFirstEntry(), 'peer.ldap.LDAPEntry');
    
      $this->assertFalse($res->getNextEntry());
    }
  }
}
