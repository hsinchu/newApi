<?php

namespace app\common\model;

use think\Model;

/**
 * SensitiveData 模型
 */
class SensitiveData extends Model
{
    protected $name = 'security_sensitive_data';

    protected $autoWriteTimestamp = true;

    protected $type = [
        'data_fields' => 'array',
    ];
}