<div class="well well-sm">
	<h3>{$title}</h3>
	<p>Here lives the documentation for the api for accessing nzb and index data. Api functions can be called by either
		logged in users, or by providing an apikey.</p>
	<br>
	{if $loggedin=="true"}
		<h3>API Credentials</h3>
		<p>Your credentials should be provided as <span style="font-family:courier;">&apikey={$userdata.api_token}</span>
		</p>
	{/if}
	<br>
	<h3>Available Functions</h3>
	<p>Use the parameter <span style="font-family:courier;">?t=</span> to specify the function being called.</p>
	<dl>
		<dt>Capabilities <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api/v1/api?t=caps">?t=caps</a></span></dt>
		<dd>Reports the capabilities if the server. Includes information about the server name, available search
			categories and version number of the newznab protocol being used.<br>Capabilities does not require any
			credentials in order to be ran.
		</dd>
		<br>
		<dt>Search <span style="font-family:courier;"><a href="{$smarty.const.WWW_TOP}/api/v1/api?t=search&amp;q=linux&amp;apikey={$userdata.api_token}">?t=search&amp;q=linux</a></span>
		</dt>
		<dd>Returns a list of nzbs matching a query. You can also filter by site category by including a comma separated
			list of categories as follows <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api?t=search&amp;cat={$catClass::GAME_ROOT},{$catClass::MOVIE_ROOT}&amp;apikey={$userdata.api_token}">?t=search&amp;cat={$catClass::GAME_ROOT}
					,{$catClass::MOVIE_ROOT}</a></span>. Include <span
					style="font-family:courier;">&amp;extended=1</span> to return extended information in the search
			results.
		</dd>
		<br>
		<dt>TV <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api/v1/api?t=tvsearch&amp;q=law%20and%20order&amp;season=7&amp;ep=12&amp;apikey={$userdata.api_token}">?t=tvsearch&amp;q=law and order&amp;season=7&amp;ep=12</a></span>
		</dt>
		<dd>Returns a list of nzbs matching a query, category, tvrageid, season or episode.
			You can also filter by site category by including a comma separated list of categories as follows:
			<span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api/v1/api?t=tvsearch&amp;rid=2204&amp;cat={$catClass::GAME_ROOT},{$catClass::MOVIE_ROOT}&amp;apikey={$userdata.api_token}">?t=tvsearch&amp;cat={$catClass::GAME_ROOT}
					,{$catClass::MOVIE_ROOT}</a></span>.
			Include <span style="font-family:courier;">&amp;extended=1</span> to return extended information in the
			search results.
		</dd>
		<dd>
			You can also supply the following parameters to do site specfic ID searches:
			&amp;rid=25056 (TVRage) &amp;tvdbid=153021 (TVDB) &amp;traktid=1393 (Trakt) &amp;tvmazeid=73 (TVMaze) &amp;imdbid=1520211
			(IMDB) &amp;tmdbid=1402 (TMDB).
		</dd>
		<br>
		<dt>Movies <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api/v1/api?t=movie&amp;imdbid=1418646&amp;apikey={$userdata.api_token}">?t=movie&amp;imdbid=1418646</a></span>
		</dt>
		<dd>Returns a list of nzbs matching a query, an imdbid and optionally a category. Filter by
			site category by including a comma separated list of categories as follows <span
					style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api/v1/api?t=movie&amp;imdbid=1418646&amp;cat={$catClass::MOVIE_SD},{$catClass::MOVIE_HD}&amp;apikey={$userdata.api_token}">?t=movie&amp;imdbid=1418646&amp;cat={$catClass::MOVIE_SD}
					,{$catClass::MOVIE_HD}</a></span>. Include <span style="font-family:courier;">&amp;extended=1</span>
			to return extended information in the search results.
		</dd>
		<br>
		<dt>Details <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api/v1/api?t=details&amp;id=9ca52909ba9b9e5e6758d815fef4ecda&amp;apikey={$userdata.api_token}">?t=details&amp;id=9ca52909ba9b9e5e6758d815fef4ecda</a></span>
		</dt>
		<dd>Returns detailed information about an nzb.</dd>
		<br>
		<dt>Info <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api/v1/api?t=info&amp;id=9ca52909ba9b9e5e6758d815fef4ecda&amp;apikey={$userdata.api_token}">?t=info&amp;id=9ca52909ba9b9e5e6758d815fef4ecda</a></span>
		</dt>
		<dd>Returns NFO contents for an NZB. Retrieve the NFO as file by specifying o=file in the request URI.</dd>
		<br>
		<dt>Get <span style="font-family:courier;"><a
						href="{$smarty.const.WWW_TOP}/api/v1/api?t=get&amp;id=9ca52909ba9b9e5e6758d815fef4ecda&amp;apikey={$userdata.api_token}">?t=get&amp;id=9ca52909ba9b9e5e6758d815fef4ecda</a></span>
		</dt>
		<dd>Downloads the nzb file associated with an Id.</dd>
	</dl>
	<br>
	<h3>Output Format</h3>
	<p>Obviously not appropriate to functions which return an nzb/nfo file.</p>
	<dl>
		<dt>Xml (default) <span style="font-family:courier;">?t=search&amp;q=linux&amp;o=xml</span></dt>
		<dd>Returns the data in an xml document.</dd>
		<br>
		<dt>Json <span style="font-family:courier;">?t=search&amp;q=linux&amp;o=json</span></dt>
		<dd>Returns the data in a json object.</dd>
	</dl>
</div>
