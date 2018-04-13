<h2>{$page->title}</h2>
<div style="text-align: center;">
	<div class="btn-group">
		<a class="btn btn-sm" title="View available Movies" href="{$smarty.const.WWW_TOP}/movies" >Movies List</a>
		<a class="btn btn-sm" title="All releases in your movies as an RSS feed" href="{$smarty.const.WWW_TOP}/rss?t=-4&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">Rss <i class="fa fa-rss"></i></a>
	</div>
</div>
<br>
{if $movies|@count > 0}
	<table class="data highlight Sortable table" id="browsetable">
		<tr>
			<th style="padding-top:0px; padding-bottom:0px;"></th>
			<th style="padding-top:0px; padding-bottom:0px;">name</th>
			<th style="padding-top:0px; padding-bottom:0px;">category</th>
			<th style="padding-top:0px; padding-bottom:0px;">added</th>
			<th class="mid" style="padding-top:0px; padding-bottom:0px;">options</th>
		</tr>
		{foreach $movies as $movie}
			<tr class="{cycle values=",alt"}">
				<td class="mid" style="width:140px">
					<div class="movcover">
						<img class="shadow img img-polaroid" src="{$smarty.const.WWW_TOP}/covers/movies/{if $movie.cover == 1}{$movie.imdbid}-cover.jpg{else}no-cover.jpg{/if}" width="120" border="0" alt="{$movie.title|escape:"htmlall"}" />
						<div class="movextra">
							<div style="text-align: center;">
								<a class="rndbtn badge badge-imdb" target="_blank" href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$movie.imdbid}" title="View Imdb">Imdb</a>
							</div>
						</div>
					</div>
				</td>
				<td>
					<h4><a title="View Movie" href="{$smarty.const.WWW_TOP}/movies/?imdb={$movie.imdbid}">{$movie.title|escape:"htmlall"} ({$movie.year})</a> </h4>
					{if isset($movie.tagline) && $movie.tagline != ''}<b>{$movie.tagline}</b><br />{/if}
					{if isset($movie.plot) && $movie.plot != ''}{$movie.plot}<br /><br />{/if}
					{if isset($movie.genre) && $movie.genre != ''}<b>Genre:</b> {$movie.genre}<br />{/if}
					{if isset($movie.director) && $movie.director != ''}<b>Director:</b> {$movie.director}<br />{/if}
					{if isset($movie.actors) && $movie.actors != ''}<b>Starring:</b> {$movie.actors}<br /><br />{/if}
				</td>
				<td class="less">{if $movie.categoryNames != ''}{$movie.categoryNames|escape:"htmlall"}{else}All{/if}</td>
				<td class="less" style="width:100px;" title="Added on {$movie.created_at}">{$movie.created_at|date_format}</td>
				<td class="mid" style="width:100px;">
					<div class="btn-group">
						<a class="btn btn-mini btn-warning mymovies" href="{$smarty.const.WWW_TOP}/mymovies/edit/{$movie.imdbid}" rel="edit" name="movies{$movie.imdbid}" title="Edit Categories">Edit</a>&nbsp;&nbsp;
						<a class="btn btn-mini btn-danger mymovies" href="{$smarty.const.WWW_TOP}/mymovies/delete/{$movie.imdbid}" rel="remove" name="movies{$movie.imdbid}" title="Remove from My Movies">Remove</a>
					</div>
				</td>
			</tr>
		{/foreach}
	</table>
{else}
	<div class="alert">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<strong>Sorry!</strong> No movies bookmarked
	</div>
{/if}
