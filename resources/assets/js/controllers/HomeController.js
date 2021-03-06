
/**
 * HomeController
 */

(function()
{
    var app = angular.module('app')


    app.controller('HomeController', ['$scope', 'Notifications', 'Products', 'Purchases', function($scope, Notifications, Products, Purchases)
    {
        // Current page
        $scope.$emit('setCurrentPage', 'Home')

        // Products
        $scope.products = []
        loadProducts()
        function loadProducts()
        {
            Products.all()
                .then(function(products)
                {
                    products.forEach(function(product) { product.quantity = 0 })

                    $scope.products = products
                })
                .catch(function()
                {
                    Notifications.error("Erreur lors de la récupération des produits")
                })
        }

        // +1 quantity
        $scope.add = function(product)
        {
            if (product.stock > product.quantity)
                product.quantity++
        }

        // -1 quantity
        $scope.remove = function(product)
        {
            if (product.quantity > 0)
                product.quantity--
        }

        $scope.isEmpty = function()
        {
            var isEmpty = true

            $scope.products.forEach(function(product)
            {
                isEmpty &= product.quantity == 0
            })

            return isEmpty
        }

        $scope.getTotal = function()
        {
            var total = 0

            $scope.products.forEach(function(product)
            {
                total += product.quantity * product.price
            })

            return total.toFixed(2)
        }

        $scope.cancel = function()
        {
            $scope.products.forEach(function(product)
            {
                product.quantity = 0
            })
        }

        $scope.validate = function()
        {
            $('#validate').modal('show')
        }

        $scope.validateSubmit = function()
        {
            Purchases.purchase($scope.products)
                .then(function()
                {
                    $('#validate').modal('hide')
                    loadProducts()
                    Notifications.success("Encaissement enregistré !")
                })
                .catch(function()
                {
                    Notifications.error("Erreur lors de la validation de l'achat")
                })
        }

    }])

})();
