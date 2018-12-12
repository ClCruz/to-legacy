var DataLayer = (function() {
    var product_list = [];

    function Ticket(idProduct, sellPrice, quantity) {
        this.idProduct = idProduct;
        this.sellPrice = sellPrice;
        this.quantity = quantity;
    }

    return {

        init: function($espetaculo) {
            this.$resumoEspetaculo = $espetaculo;
            this.eventoId = this.$resumoEspetaculo.data('evento');
            this.cacheDOM();
        },

        cacheDOM: function() {
            this.$pedidoResumo = this.$resumoEspetaculo.find('#pedido_resumo');
            this.$tiposIngressoCel = this.$pedidoResumo.find('td.tipo');
            this.$spanTotalIngresso = this.$pedidoResumo.find('span.valorIngresso');
        },

        build: function() {
            var tmpList = [],
                totalIngressos = this.$spanTotalIngresso.length,
                eventoId = this.eventoId;

            var ticket = new Ticket(eventoId, this.$spanTotalIngresso.eq(0).text(), totalIngressos);
            product_list.push(ticket);
        },

        getProductList: function() {
            return product_list;
        }

    }

} ());