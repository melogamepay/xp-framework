<?php
/* This socket is part of the XP framework's experiments
 *
 * $Id$
 */

  uses('io.streams.InputStream', 'peer.Socket');

  /**
   * InputStream that reads from a socket
   *
   * @purpose  InputStream implementation
   */
  class SocketInputStream extends Object implements InputStream {
    protected
      $socket= NULL;
    
    /**
     * Constructor
     *
     * @param   peer.Socket socket
     */
    public function __construct(Socket $socket) {
      $this->socket= $socket;
      $this->socket->isConnected() || $this->socket->connect();
    }

    /**
     * Read a string
     *
     * @param   int limit default 8192
     * @return  string
     */
    public function read($limit= 8192) {
      return $this->socket->read($limit);
    }

    /**
     * Returns the number of bytes that can be read from this stream 
     * without blocking.
     *
     */
    public function available() {
      return $this->socket->eof() ? -1 : 1;
    }

    /**
     * Close this buffer
     *
     */
    public function close() {
      $this->socket->close();
    }

    /**
     * Destructor. Ensures socket is closed.
     *
     */
    public function __destruct() {
      $this->socket->isConnected() && $this->close();
    }

    /**
     * Creates a string representation of this socket
     *
     * @return  string
     */
    public function toString() {
      return $this->getClassName().'<'.$this->socket->toString().'>';
    }
  }
?>