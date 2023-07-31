<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.2.2
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2023 KONKORD DIGITAL
 */

return [
    'products' => 'Produtos',
    'product' => 'Produto',
    'create' => 'Criar Produto',
    'edit' => 'Editar Produto',
    'export' => 'Exportar Produtos',
    'import' => 'Importar Produtos',
    'created' => 'Produto criado com sucesso',
    'updated' => 'Produto atualizado com sucesso',
    'deleted' => 'Produto excluído com sucesso',
    'related_products' => 'Produtos Relacionados',
    'manage' => 'Gerenciar Produtos',
    'name' => 'Nome',
    'description' => 'Descrição',
    'table_heading' => 'Produto',
    'tax' => 'Taxa',
    'quantity' => 'Quantidade',
    'qty' => 'QTD',
    'unit_price' => 'Preço Unitário',
    'direct_cost' => 'Custo Direto',
    'unit' => 'Unidade (kg, lots)',
    'sku' => 'SKU',
    'product_name' => 'Nome do Produto',
    'product_quantity' => 'Quantidade do Produto',
    'product_unit' => 'Unidade do Produto',
    'product_sku' => 'SKU do Produto',
    'is_active' => 'Ativo',
    'tax_rate' => 'Taxa de Imposto',
    'tax_label' => 'Rótulo da Taxa',
    'tax_percent' => 'Porcentagem da Taxa',
    'discount' => 'Desconto',
    'amount' => 'Valor',
    'discount_percent' => 'Porcentagem de Desconto',
    'discount_amount' => 'Quantia de Desconto',
    'will_be_added_as_new' => ':name será adicionado como novo produto',
    'total_products' => 'Total de Produtos',
    'total_sold' => 'Total Vendido',
    'sold_amount_exc_tax' => 'Valor Vendido (tax exl.)',
    'interest_in_product' => 'Interesse no Produto',
    'resource_has_no_products' => 'Nenhum produto criado, comece adicionando produtos',
    'deal_info' => 'Vincule produtos ao negócio para criar uma lista dos produtos aos quais o negócio está relacionado, o valor do negócio é calculado automaticamente com base nos produtos adicionados.',
    'exists_in_trash_by_name' => 'Já existe um produto com o mesmo nome na lixeira. Deseja restaurar o produto descartado?',
    'add_to_deal' => 'Adicionar produtos no negócio',
    'choose_or_enter' => 'Escolha ou insira um produto',
    'cards' => [
        'performance' => 'Desempenho do produto',
        'performance_info' => 'A coluna "Interesse no produto" reflete todos os produtos que são adicionados aos negócios, mas a coluna "Total vendido" reflete os produtos que são adicionados aos negócios e os negócios são marcados como ganhos',
    ],
    'count' => '0 produtos | 1 produto | :count produtos',
    'settings' => [
        'default_tax_type' => 'Você vende seus produtos a taxas que incluem impostos?',
        'default_discount_type' => 'Tipo de desconto padrão',
    ],
    'actions' => [
        'mark_as_active' => 'Marcar como ativo',
        'mark_as_inactive' => 'Marcar como inativo',
        'update_unit_price' => 'Atualizar preço',
        'update_tax_rate' => 'Atualizar taxa de imposto',
    ],
    'validation' => [
        'sku' => [
            'unique' => 'Um produto com este SKU já existe.',
        ],
    ],
    'empty_state' => [
        'title' => 'Você não criou nenhum produto.',
        'description' => 'Economize tempo usando produtos predefinidos.',
    ],
];
