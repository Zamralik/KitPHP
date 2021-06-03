<?php

namespace KitPHP\Tool;

use \Exception;

/*

url-example://master-account:Abc123Def456g@www.exemple.com:12345678/directory/file.ext?property-name=value#content-title
|-origin----------------------------------------------------------||-path------------||-appendix-----------------------|
|-scheme---|//|-authority-----------------------------------------||-path------------|?|-query-----------|#|-fragment--|
|-scheme---|//|-authentication-----------|@|-address--------------||-path------------|?|-query-----------|#|-fragment--|
|-scheme---|//|-username---|:|-password--|@|-host--------|:|-port-||-path------------|?|-query-----------|#|-fragment--|

You can get any block named above, but can only set the atomic ones.
The query setter wants a hashmap that will be serialized.
You're responsible for encoding special characters of the host and path.
Elements that are automatically encoded using rawurlencode() :
• username
• password
• query property names & values
• fragment

If you have no username, authentication will be empty
If you have no host, address and authority will be empty

When serializing:
If you have no host and no path, scheme will be ignored
If you have no path and both origin and appendix, everything will be ignored

*/

class Url
{
	private $scheme = null;
	private $username = null;
	private $password = null;
	private $host = null;
	private $port = null;
	private $path = '';
	private $query = null;
	private $fragment = null;

	public function __construct($url = null)
	{
		if (isset($url))
		{
			$this->parse($url);
		}
	}

	public function __toString()
	{
		return $this->serialize();
	}

	/* ------ */
	/* GETTER */
	/* ------ */

	/**
	* @return string
	*/
	public function serialize()
	{
		$origin = $this->getOrigin();
		$path = $this->getPath();
		$appendix = $this->getAppendix();

		if ($path)
		{
			return $origin . $path . $appendix;
		}

		if (empty($origin))
		{
			return $appendix ?: '';
		}

		$host = $this->getHost();

		if (empty($host))
		{
			return $appendix ?: '';
		}

		if (empty($appendix))
		{
			return $origin ?: '';
		}

		return '';
	}

	/**
	* @return string|null
	*/
	public function getOrigin()
	{
		$origin = '';

		$scheme = $this->getScheme();
		$authority = $this->getAuthority();

		if ($scheme)
		{
			$origin .= $scheme;
		}

		if ($authority)
		{
			$origin .= '//' . $authority;
		}

		return $origin ?: null;
	}

	/**
	* @return string|null
	*/
	public function getAuthority()
	{
		$authority = '';

		$address = $this->getAddress();

		if ($address)
		{
			$authentication = $this->getAuthentication();
			if ($authentication)
			{
				$authority .= $authentication . '@';
			}

			$authority .= $address;
		}

		return $authority ?: null;
	}

	/**
	* @return string|null
	*/
	public function getAddress()
	{
		$address = '';

		$host = $this->getHost();

		if ($host)
		{
			$address .= $host;

			$port = $this->getPort();

			if ($port)
			{
				$address .= ':' . $port;
			}
		}

		return $address ?: null;
	}

	/**
	* @return string|null
	*/
	public function getAuthentication()
	{
		$authentication = '';

		$username = $this->getUsername();

		if ($username)
		{
			$authentication .= $username;

			$password = $this->getPassword();

			if ($password)
			{
				$authentication .= ':' . $password;
			}
		}

		return $authentication ?: null;
	}

	/**
	* @return string|null
	*/
	public function getAppendix()
	{
		$appendix = '';

		$query = $this->getQuery();
		$fragment = $this->getFragment();

		if ($query)
		{
			$appendix .= '?' . $query;
		}

		if ($fragment)
		{
			$appendix .= '#'. $fragment;
		}

		return $appendix ?: null;
	}

	/**
	* @return string|null
	*/
	public function getScheme()
	{
		return $this->scheme;
	}

	/**
	* @return string|null
	*/
	public function getUsername()
	{
		return $this->username;
	}

	/**
	* @return string|null
	*/
	public function getPassword()
	{
		return $this->password;
	}

	/**
	* @return string|null
	*/
	public function getHost()
	{
		return $this->host;
	}

	/**
	* @return int|null
	*/
	public function getPort()
	{
		return $this->port;
	}

	/**
	* @return string
	*/
	public function getPath()
	{
		return $this->path;
	}

	/**
	* @return string|null
	*/
	public function getQuery()
	{
		return $this->query;
	}

	/**
	* @return string|null
	*/
	public function getFragment()
	{
		return $this->fragment;
	}

	/* ------ */
	/* SETTER */
	/* ------ */

	/**
	* @param string $url
	*/
	public function parse(string $url)
	{
		$parts = isset($url) ? parse_url($url) : [];
		$this->scheme = empty($parts['scheme']) ? null : ($parts['scheme'] . ':');
		$this->username = $parts['user'] ?? null;
		$this->password = $parts['pass'] ?? null;
		$this->host = $parts['host'] ?? null;
		$this->port = $parts['port'] ?? null;
		$this->path = $parts['path'] ?? '';
		$this->query = $parts['query'] ?? null;
		$this->fragment = $parts['fragment'] ?? null;
	}

	/**
	* @param string $scheme
	*/
	public function setScheme(string $scheme)
	{
		$scheme = strtolower($scheme);

		if (!preg_match('~^[a-z]+(-[a-z]+)*:$~', $scheme))
		{
			throw new Exception('Invalid scheme');
		}

		$this->scheme = $scheme;
	}

	/**
	* @param string $username
	*/
	public function setUsername(string $username)
	{
		$this->username = rawurlencode($username);
	}

	/**
	* @param string $password
	*/
	public function setPassword(string $password)
	{
		$this->password = rawurlencode($password);
	}

	/**
	* @param string $host
	*/
	public function setHost(string $host)
	{
		$host = strtolower($host);

		if (preg_match('~^[0-9.]+$~', $host))
		{
			if (false === filter_var($host, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4))
			{
				throw new Exception('Invalid IPv4 Host');
			}
		}
		elseif ($host[0] === '[' && substr($host, -1) === ']')
		{
			if (false === filter_var(substr($host, 1, -1), \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6))
			{
				throw new Exception('Invalid IPv6 Host');
			}
		}
		else
		{
			if (false === filter_var($host, \FILTER_VALIDATE_DOMAIN, \FILTER_FLAG_HOSTNAME))
			{
				throw new Exception('Invalid domain host');
			}
		}

		$this->host = $host;
	}

	/**
	* @param int $port
	*/
	public function setPort(int $port)
	{
		if (empty($port) || $port < 1)
		{
			throw new Exception('Invalid port');
		}

		$this->port = $port;
	}

	/**
	* @param string $path
	*/
	public function setPath(string $path)
	{
		$this->path = $path;
	}

	/**
	* @param string|array $query
	*/
	public function setQuery($query)
	{
		$this->query = is_array($query) ? http_build_query($hashmap) : $query;
	}

	/**
	* @param string $fragment
	*/
	public function setFragment(string $fragment)
	{
		$this->fragment = rawurlencode($fragment);
	}

	/* ------ */
	/* REMOVE */
	/* ------ */

	public function removeScheme()
	{
		$this->scheme = null;
	}

	public function removeUsername()
	{
		$this->username = null;
	}

	public function removePassword()
	{
		$this->password = null;
	}

	public function removeHost()
	{
		$this->host = null;
	}

	public function removePort()
	{
		$this->port = null;
	}

	public function removePath()
	{
		$this->path = '';
	}

	public function removeQuery()
	{
		$this->query = null;
	}

	public function removeFragment()
	{
		$this->fragment = null;
	}
}
