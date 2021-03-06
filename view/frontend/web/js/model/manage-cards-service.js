define(
    [
        'ko',
        'jquery',
        'mage/translate'
    ],
    function(ko, $, $t) {
        'use strict';
        var iframeHeight = ko.observable('640px');
        var iframeWidth = ko.observable('100%');
        var iframeUrl = ko.observable($('#paytpv-iframe').val());
        var displayMessage =  ko.observable(false);
       
        
        return {
            iframeHeight: iframeHeight,
            iframeWidth: iframeWidth,
            iframeUrl: iframeUrl,
            displayMessage: displayMessage,

            iframeResize: function(event) {
                var data = JSON.parse(event);
                if (data.iframe) {
                    if (this.iframeHeight() != data.iframe.height) {
                        this.iframeHeight(data.iframe.height);
                    }
                    if (this.iframeWidth() != data.iframe.width) {
                        this.iframeWidth(data.iframe.width);
                    }
                }
            }
        };
    });
