<?php

namespace SnapGame;

use Silex\Application;

class BaseApplication extends Application
{
    use Application\TwigTrait;
    use Application\UrlGeneratorTrait;
}
