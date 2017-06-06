<?php

namespace App;

class Submission extends UuidBase
{
    protected $table = 'submissions';

    protected $guarded = [
        'id',
    ];

    public function conference()
    {
        return $this->belongsTo(Conference::class);
    }

    public function talkRevision()
    {
        return $this->belongsTo(TalkRevision::class);
    }
}
