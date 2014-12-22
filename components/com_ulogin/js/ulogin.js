if ( (typeof jQuery === 'undefined') && !window.jQuery ) {
    document.write(unescape("%3Cscript type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js'%3E%3C/script%3E%3Cscript type='text/javascript'%3EjQuery.noConflict();%3C/script%3E"));
} else {
    if((typeof jQuery === 'undefined') && window.jQuery) {
        jQuery = window.jQuery;
    } else if((typeof jQuery !== 'undefined') && !window.jQuery) {
        window.jQuery = jQuery;
    }
}

function uloginCallback(token){
    jQuery.ajax({
        url: '/index.php?option=com_ulogin&task=login',
        type: 'POST',
        dataType: 'json',
        cache: false,
        data: {token: token},
        success: function (data) {
            if (!data.success && data.message) {
                uloginMessage({error : [data.message]});
                return;
            }

            if (data.messages) {
                uloginMessage(data.messages);
            }

            if (data.data.networks) {
                if (jQuery('.ulogin_accounts').length > 0){
                    addUloginProviderBlock(data.data.networks, data.messages);
                } else {
                    location.reload();
                }
            }

            if (data.data.script) {
                var token = data.data.script['token'],
                    identity = data.data.script['identity'];
                if  (token && identity) {
                    uLogin.mergeAccounts(token, identity);
                } else if (token) {
                    uLogin.mergeAccounts(token);
                }
            }
        }
    });
}


function uloginMessage(messages) {
    var rendering = false;

    if (!messages) {
        return;
    }

    for(var type in messages) {
        if (messages[type]) {
            for (var k = 0; k < messages[type].length; k++) {
                if (messages[type][k]) {
                    rendering = true;
                    break;
                }
            }
            if (rendering) {
                break;
            }
        }
    }

    if (rendering) {
        Joomla.renderMessages(messages);
        setTimeout(function () {
            Joomla.removeMessages();
        }, 3000);
    }
}


function uloginDeleteAccount(network){
    jQuery.ajax({
        url: '/index.php?option=com_ulogin&task=delete_account',
        type: 'POST',
        dataType: 'json',
        cache: false,
        data: {network: network},
        error: function (data, textStatus, errorThrown) {
            alert('Не удалось выполнить запрос');
        },
        success: function (data) {
            if (!data.success && data.message) {
                uloginMessage({error : [data.message]});
                return;
            }

            if (Object.keys(data.messages)[0] !== 'error') {
                var accounts = jQuery('.ulogin_accounts'),
                    nw = accounts.find('[data-ulogin-network='+network+']');
                if (nw.length > 0) nw.hide();

                if (accounts.find('.ulogin_provider:visible').length == 0) {
                    var delete_str = jQuery('.ulogin_form').find('.delete_str');
                    if (delete_str.length > 0) delete_str.hide();
                }
            }

            if (data.messages) {
                uloginMessage(data.messages);
            }
        }
    });
}


function addUloginProviderBlock(networks, messages) {
    var uAccounts = jQuery('.ulogin_accounts');
    uAccounts.each(function(){
        for(var k=0; k < networks.length; k++) {
            var network = networks[k],
                uNetwork = jQuery(this).find('[data-ulogin-network=' + network + ']');

            if (uNetwork.length == 0) {
                var onclick = '';
                if (jQuery(this).hasClass('can_delete')) {
                    onclick = ' onclick="uloginDeleteAccount(\'' + network + '\')"';
                }
                jQuery(this).append(
                    '<div data-ulogin-network="' + network + '" class="ulogin_provider big_provider ' + network + '_big"' + onclick + '></div>'
                );
                if (messages) uloginMessage(messages);
            } else {
                if (uNetwork.is(':hidden')) {
                    if (messages) uloginMessage(messages);
                }
                uNetwork.show();
            }
        }

        var uFrom = uAccounts.parent(),
            delete_str = uFrom.find('.delete_str');
        if (delete_str.length > 0) delete_str.show();

    });
}
