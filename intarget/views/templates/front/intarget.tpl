<!-- Module Block inTarget Code-->
{$intargetjscode|escape:'UTF-8'}
{if isset($account_created)}
    <script type='text/javascript'>
        $(function() {
            console.log('inTarget user reg');
            inTarget.event('user-reg');
        });
    </script>
{/if}
{$intrgt_idord}
<!-- /MODULE Block inTarget Code-->