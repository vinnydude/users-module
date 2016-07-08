<?php namespace Anomaly\UsersModule\User\Support\RelationshipFieldType;

use Anomaly\UsersModule\User\Table\Filter\StatusFilterQuery;

/**
 * Class LookupTableBuilder
 *
 * @link          http://pyrocms.com/
 * @author        PyroCMS, Inc. <support@pyrocms.com>
 * @author        Ryan Thompson <ryan@pyrocms.com>
 * @package       Anomaly\UsersModule\User\Support\RelationshipFieldType
 */
class LookupTableBuilder extends \Anomaly\RelationshipFieldType\Table\LookupTableBuilder
{

    protected $filters = [
        'search' => [
            'filter' => 'search',
            'fields' => [
                'display_name',
                'username',
                'email'
            ]
        ],
        'roles',
        'status' => [
            'filter'  => 'select',
            'query'   => StatusFilterQuery::class,
            'options' => [
                'active'   => 'anomaly.module.users::field.status.option.active',
                'inactive' => 'anomaly.module.users::field.status.option.inactive',
                'disabled' => 'anomaly.module.users::field.status.option.disabled'
            ]
        ]
    ];

    protected $columns = [
        'display_name',
        'username',
        'email',
        'status' => [
            'value' => 'entry.status_label'
        ]
    ];
}
