<?php

declare(strict_types=1);

/**
 * SessionHandlerAdapter
 *
 * Adapter class to bridge CakeSessionHandlerInterface with PHP's native SessionHandlerInterface.
 * This allows CakePHP's session handlers to work with PHP's native session handling.
 *
 * @package       Cake.Model.Datasource
 */
class SessionHandlerAdapter implements SessionHandlerInterface, SessionIdInterface, SessionUpdateTimestampHandlerInterface
{
    /**
     * Constructor
     *
     * @param CakeSessionHandlerInterface $cakeSessionHandler The CakePHP session handler to wrap
     */
    public function __construct(
        private CakeSessionHandlerInterface $cakeSessionHandler,
    ) {
    }

    /**
     * Close the session
     *
     * @return bool Success
     */
    public function close()
    {
        return $this->cakeSessionHandler->close();
    }

    /**
     * Destroy a session
     *
     * @param string $id The session ID
     * @return bool Success
     */
    public function destroy(string $id)
    {
        return $this->cakeSessionHandler->destroy($id);
    }

    /**
     * Garbage collection
     *
     * @param int $max_lifetime Session max lifetime in seconds
     * @return int|bool Number of deleted sessions or success status
     */
    public function gc(int $max_lifetime)
    {
        return $this->cakeSessionHandler->gc($max_lifetime);
    }

    /**
     * Open a session
     *
     * @param string $path The session save path
     * @param string $name The session name
     * @return bool Success
     */
    public function open(string $path, string $name)
    {
        //Cake interface ignores these parameters.
        return $this->cakeSessionHandler->open();
    }

    /**
     * Read session data
     *
     * @param string $id The session ID
     * @return string|false The session data or false on failure
     */
    public function read(string $id)
    {
        return $this->cakeSessionHandler->read($id);
    }

    /**
     * Write session data
     *
     * @param string $id The session ID
     * @param string $data The session data
     * @return bool Success
     */
    public function write(string $id, string $data)
    {
        return $this->cakeSessionHandler->write($id, $data);
    }

    /**
     * Create a new session ID
     *
     * PHP 8.6 deprecates session handlers without create_sid() (required in PHP 9.0).
     * Generates an ID in the same format as PHP's default (32 hexadecimal characters).
     * session_create_id() is not used because it would call this method recursively.
     *
     * @return string The new session ID
     */
    public function create_sid(): string // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- defined by SessionIdInterface
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Validate that a session with the ID exists
     *
     * Called only when session.use_strict_mode is enabled (the default since PHP 8.6).
     *
     * @param string $id The session ID
     * @return bool Whether the session exists
     */
    public function validateId(string $id): bool
    {
        $data = $this->read($id);

        return is_string($data) && $data !== '';
    }

    /**
     * Update the timestamp of the session
     *
     * @param string $id The session ID
     * @param string $data The session data
     * @return bool Success
     */
    public function updateTimestamp(string $id, string $data): bool
    {
        return (bool)$this->write($id, $data);
    }
}
