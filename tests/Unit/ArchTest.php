<?php

arch()->preset()->laravel();
arch()->preset()->security();

arch()->expect('dd')->not->toBeUsed();
