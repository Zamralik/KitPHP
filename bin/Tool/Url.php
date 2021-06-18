<?php

namespace KitPHP\Tool;

use \Exception;

/*

url-example://master-account:Abc123Def456g@www.exemple.com:12345678/directory/file.ext?property-name=value#content-title
|-origin----------------------------------------------------------||-path------------||-appendix-----------------------|
|-scheme--|://|-authority-----------------------------------------||-path------------|?|-query-----------|#|-fragment--|
|-scheme--|://|-authentication-----------|@|-address--------------||-path------------|?|-query-----------|#|-fragment--|
|-scheme--|://|-username---|:|-password--|@|-host--------|:|-port-||-path------------|?|-query-----------|#|-fragment--|

You can get any block named above, but can only set the atomic ones.
The query setter wants a hashmap that will be serialized.
You're responsible for encoding special characters of the host and path.
Elements that are automatically encoded :
• username
• password
• path
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

	/**
	* @param string $url
	*/
	public function __construct(string $url = null)
	{
		if (isset($url))
		{
			$this->parse($url);
		}
	}

	/**
	* @return string
	*/
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

		return $origin . $path . $appendix;
	}

	/**
	* @return string|null
	*/
	public function getOrigin()
	{
		$authority = $this->getAuthority();

		if (empty($authority))
		{
			return null;
		}

		$scheme = $this->getScheme();

		if (empty($scheme))
		{
			return '//' . $authority;
		}

		return $scheme . '://' . $authority;
	}

	/**
	* @return string|null
	*/
	public function getAuthority()
	{
		$address = $this->getAddress();

		if (empty($address))
		{
			return null;
		}

		$authentication = $this->getAuthentication();

		if (empty($authentication))
		{
			return $address;
		}

		return $authentication . '@' . $address;
	}

	/**
	* @return string|null
	*/
	public function getAddress()
	{
		$host = $this->getHost();

		if (empty($host))
		{
			return null;
		}

		$port = $this->getPort();

		if (empty($port))
		{
			return $host;
		}

		return $host . ':' . $port;
	}

	/**
	* @return string|null
	*/
	public function getAuthentication()
	{
		$username = $this->getUsername();

		if (empty($username))
		{
			return null;
		}

		$password = $this->getPassword();

		if (empty($password))
		{
			return $username;
		}

		return $username . ':' . $password;
	}

	/**
	* @return string|null
	*/
	public function getAppendix()
	{
		$query = $this->getQuery();
		$fragment = $this->getFragment();

		if (empty($query) && empty($fragment))
		{
			return null;
		}

		$appendix = '';

		if (isset($query))
		{
			$appendix .= '?' . $query;
		}

		if (isset($fragment))
		{
			$appendix .= '#'. $fragment;
		}

		return $appendix;
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
	* @return array
	*/
	public function getParameters()
	{
		if (empty($this->query))
		{
			return [];
		}

		parse_str($this->query, $parameters);

		return $parameters;
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
		$parts = parse_url($url);

		// Scheme

		if (empty($parts['scheme']))
		{
			$this->scheme = null;
		}
		else
		{
			$this->setScheme($parts['scheme']);
		}

		// Username

		if (empty($parts['user']))
		{
			$this->username = null;
		}
		else
		{
			$this->setUsername(rawurldecode($parts['user']));
		}

		// Password

		if (empty($parts['pass']))
		{
			$this->password = null;
		}
		else
		{
			$this->setPassword(rawurldecode($parts['pass']));
		}

		// Host

		if (empty($host))
		{
			$this->host = null;
		}
		else
		{
			$this->setHost($parts['host']);
		}

		// Port

		if (empty($port))
		{
			$this->port = null;
		}
		else
		{
			$this->setPort($parts['port']);
		}

		// Path

		if (empty($parts['path']))
		{
			$this->path = '/';
		}
		else
		{
			$this->setPath(rawurldecode($parts['path']));
		}

		// Query

		if (empty($parts['query']))
		{
			$this->query = null;
		}
		else
		{
			$this->setQuery($parts['query']);
		}

		// Fragment

		if (empty($parts['fragment']))
		{
			$this->fragment = null;
		}
		else
		{
			$this->setFragment(rawurldecode($parts['fragment']));
		}
	}

	/**
	* @param string $scheme
	*/
	public function setScheme(string $scheme)
	{
		if (empty($scheme))
		{
			throw new Exception('Empty scheme');
		}

		$scheme = strtolower($scheme);

		if (!preg_match('~^[a-z]+(-[a-z]+)*$~', $scheme))
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
		if (empty($username))
		{
			throw new Exception('Empty username');
		}

		$this->username = rawurlencode($username);
	}

	/**
	* @param string $password
	*/
	public function setPassword(string $password)
	{
		if (empty($password))
		{
			throw new Exception('Empty password');
		}

		$this->password = rawurlencode($password);
	}

	/**
	* @param string $host
	*/
	public function setHost(string $host)
	{
		if (empty($host))
		{
			throw new Exception('Empty host');
		}

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
		if (empty($port) || $port < 1  || $port > 65535)
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
		if (empty($path) || $path[0] !== '/')
		{
			throw new Exception('Invalid path');
		}

		$path = strtolower($path);
		$path = rawurlencode($path);
		$path = str_replace('%2F', '/', $path);

		$this->path = $path;
	}

	/**
	* It is safer to use setParameters()
	*
	* @param string $query The value is not validated
	*/
	public function setQuery(string $query)
	{
		if (empty($query))
		{
			throw new Exception('Empty query');
		}

		$this->query = $query;
	}

	/**
	* @param array $parameters
	*/
	public function setParameters(array $parameters)
	{
		if (empty($parameters))
		{
			throw new Exception('Empty parameters');
		}

		$this->query = http_build_query($parameters);
	}

	/**
	* @param string $fragment
	*/
	public function setFragment(string $fragment)
	{
		if (empty($fragment))
		{
			throw new Exception('Empty fragment');
		}

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
		$this->port = '/';
	}

	public function removeQuery()
	{
		$this->query = null;
	}

	public function removeParameters()
	{
		$this->query = null;
	}

	public function removeFragment()
	{
		$this->fragment = null;
	}
}
