{if !empty($harvardDetails.authors)}{$harvardDetails.authors|escape} {/if}
{if !empty($harvardDetails.year)}({$harvardDetails.year|escape}). {/if}
<span style="font-style:italic;">{$harvardDetails.title|escape}</span>
{if !empty($harvardDetails.edition)} {$harvardDetails.edition|escape} {/if}
{if !empty($harvardDetails.publisher)}{$harvardDetails.publisher|escape}. {/if}