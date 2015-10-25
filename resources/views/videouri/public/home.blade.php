@extends('app')

@section('content')
<div id="filter-options" class="row">
    <div class="col-xs-5">
        <div class="btn-group">
            <button class="btn btn-white choosen-source">Source: All</button>
            <button class="btn btn-white dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
            </button>
            <span class="dropdown-arrow dropdown-arrow-inverse"></span>
            <ul class="dropdown-menu dropdown-inverse">
                <li>
                    <a href="#" class="video-source" data-filter="*"> All </a>
                </li>

                <?php foreach($apis as $api): ?>
                <li>
                    <a href="#" class="video-source" data-filter=".<?= $api ?>"> <?= $api ?> </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    {{-- <div class="col-md-7 text-right">
        <h3 style="color: white; margin: 0; text-shadow: 5px 3px 1px #c0392b">Today's most viewed videos</h3>
    </div> --}}

    <?php if (false): // @TODO ?>
    <div class="col-xs-5 text-right" id="options-block">
        <div class="btn-group">
            <button class="btn btn-white">Sort</button>
            <button class="btn btn-white dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
            </button>
            <span class="dropdown-arrow dropdown-arrow-inverse"></span>
            <ul class="dropdown-menu dropdown-inverse">
                <li>
                    <a href="#" class="video-sort" data-source="popular"> <?= lang('popular_videos') ?> </a>
                </li>
                <li>
                    <a href="#" class="video-sort" data-source="top_rated"> <?= lang('toprated_videos') ?> </a>
                </li>
                <li>
                    <a href="#" class="video-sort" data-source="most_viewed"> <?= lang('mostviewed_videos') ?> </a>
                </li>
            </ul>
        </div>

        <div class="btn-group">
            <button class="btn btn-white">Period</button>
            <button class="btn btn-white dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
            </button>
            <span class="dropdown-arrow dropdown-arrow-inverse"></span>
            <ul class="dropdown-menu dropdown-inverse">
                <?php foreach($time as $name => $attr): ?>
                <li>
                    <a href="#" class="video-period" data-source="<?= $attr ?>"> <?= ucfirst($name) ?> </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div> <!-- Options block -->
    <?php endif; ?>
</div>

<div id="videos" class="row">
<?php if (!$fakeContent): ?>
<?php foreach ($data as $sort => $videos):  ?>
    <?php foreach ($videos as $video): ?>

    <div class="col-md-4 <?= $sort ?> <?= $video['source'] ?>">
        <div class="video">
            <div class="image">
                <a href="<?= $video['url'] ?>">
                    <img src="<?= $video['thumbnail'] ?>" alt="<?= $video['title'] ?>" class="img-responsive"/>
                </a>
                <span class="fui-play" style="position: absolute; top: 35%; left: 45%; color: #fff; font-size: 30px; text-shadow: 0px 0px 20px #000, 1px -3px 0px #45c8a9" data-url="<?= $video['url'] ?>"></span>
            </div>

            <span class="source <?= $video['source'] ?>">
                <?= $video['source'] ?>
            </span>

            <h1 class="title">
                <a href="<?= $video['url'] ?>" title="<?= $video['title'] ?>">
                    <?= $video['title'] ?>
                </a>
            </h1>

            <?php if (false): // @TODO ?>
            <div class="tile-sidebar hidden">
                <ul class="list-unstyled" style="position: relative;">
                    <li>
                        <button class="close">
                            <span class="fui-time text-muted"
                                  data-toggle="tooltip" title="5:30"></span>
                        </button>
                    </li>
                    <?php if (isset($video['category'])): ?>
                    <li>
                        <button class="dropdown-toggle" data-toggle="dropdown">
                            <span class="fui-list text-muted"></span>
                        </button>
                        <span class="dropdown-arrow dropdown-arrow-inverse"></span>
                        <ul class="dropdown-menu dropdown-inverse">
                            <?php foreach($video['category'] as $category): ?>
                            <li>
                                <a href="/category/<?= $category ?>"> <?= $category ?> </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif ?>
        </div>
    </div>

    <?php endforeach; //$video ?>
<?php endforeach; //$sort, $videos ?>
<?php endif; ?>
</div>
@endsection

@section('scripts')
<script type="text/javascript" src="{{ videouri_asset('/js/modules/videosListing.js') }}"></script>
@endsection
