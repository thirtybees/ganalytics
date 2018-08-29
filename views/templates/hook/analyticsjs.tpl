{*
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark & property of PrestaShop SA
 *}
{if Configuration::get('GA_OPTIMIZE_ID')}
{literal}
  <style>.async-hide { opacity: 0 !important} </style>
  <script data-cookieconsent="statistics">(function(a,s,y,n,c,h,i,d,e){s.className+=' '+y;h.start=1*new Date;
      h.end=i=function(){s.className=s.className.replace(RegExp(' ?'+y),'')};
      (a[n]=a[n]||[]).hide=h;setTimeout(function(){i();h.end=null},c);h.timeout=c;
    })(window,document.documentElement,'async-hide','dataLayer',{/literal}{Configuration::get('GA_OPTIMIZE_TIMER')|escape:'javascript':'UTF-8'}{literal},
            {'{/literal}{Configuration::get('GA_OPTIMIZE_ID')}{literal}':true});
  </script>
{/literal}
{/if}
<script type="text/javascript" data-cookieconsent="statistics">
  {literal}
  (window.gaDevIds=window.gaDevIds||[]).push('xhHp2h');
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
  {/literal}
  ga('create', '{Configuration::get('GA_ACCOUNT_ID')|escape:'javascript':'UTF-8'}', 'auto');
  {if $IP_ENABLED}
  ga('set', 'anonymizeIp', true);
  {/if}
  ga('require', 'ec');
  {if Configuration::get('GA_OPTIMIZE_ID')}
  ga('require', '{Configuration::get('GA_OPTIMIZE_ID')}');
  {/if}
  {if $userId && !$backOffice}ga('set', 'userId', '{$userId|escape:'javascript':'UTF-8'}');{/if}
  {if $backOffice}ga('set', 'nonInteraction', true);{/if}
</script>

<script type="text/javascript" data-cookieconsent="statistics">
  var isTracking = false;
  ga(function(tracker) {
    window.isTracking = true;
  });
  setTimeout(function(){
    if(!isTracking)
    {
      function uuidv4() {
        var uuid = "", i, random;
        for (i = 0; i < 32; i++) {
          random = Math.random() * 16 | 0;
          if (i == 8 || i == 12 || i == 16 || i == 20) {
            uuid += "-"
          }
          uuid += (i == 12 ? 4 : (i == 16 ? (random & 3 | 8) : random)).toString(16);
        }
        return uuid;
      }

      $.ajax({
        type: 'post',
        data: "trackid={Configuration::get('GA_ACCOUNT_ID')|escape:'javascript':'UTF-8'}&site="+baseDir+"&page="+location.pathname+'&uid='+uuidv4(),
        url: baseDir + 'modules/ganalytics/ajax.php',
        success: function(data) {
        }
      })
    }
  }, 5000)

</script>
