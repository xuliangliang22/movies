<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/2 0002
 * Time: 下午 4:46
 */
namespace App\Console\Commands\Mytraits;

use Illuminate\Support\Facades\DB;

trait Douban
{

    /**
     * 调用豆瓣命令完善信息
     */
    public function perfectContent($aid = null)
    {
        global $douban_data;
        $this->info('perfect douban content update start!');
        try {
            $take = 10;
            do {
                $movies = DB::connection($this->dbName)->table($this->tableName)->where('id', '>', $this->aid)->where('typeid', $this->typeId)->where('is_douban',-1)->take($take)->orderBy('id')->get();
                if(count($movies) < 1){
                    $this->error('no content to douban');
                    break;
                }

                $tot = count($movies);
                $updateArr = [];
                foreach ($movies as $key => $row) {
                    $this->aid = $row->id;
                    $this->info("this is id {$row->id} title {$row->title}");
                    $this->call('caiji:douban', ['keyword' => $row->title]);

                    foreach ($row as $k => $v) {
                        switch ($k) {
                            case 'grade':
                                if ((empty($v) || $v > 10) && isset($douban_data['grade'])) {
                                    $updateArr['grade'] = $douban_data['grade'];
                                }
                                break;
                            case 'litpic':
                                if (empty($v) && isset($douban_data['litpic'])) {
                                    $updateArr['litpic'] = $douban_data['litpic'];
                                }
                                break;
                            case 'body':
                                if (empty($v) && isset($douban_data['body'])) {
                                    $body = $douban_data['body'];
                                    if(mb_strlen($body) > 250){
                                        $body = mb_substr($body,0,250).'....';
                                    }
                                    $updateArr['body'] = $body;
                                }
                                break;
                            case 'director':
                                if (empty($v) && isset($douban_data['html']['director'])) {
                                    $updateArr['director'] = $douban_data['html']['director'];
                                }
                                break;
                            case 'actors':
                                if (empty($v) && isset($douban_data['html']['actors'])) {
                                    $actors = $douban_data['html']['actors'];
                                    if(mb_strlen($actors,'utf-8') > 250){
                                        $actors = explode(',',$actors);
                                        $actors = array_slice($actors,0,5);
                                        $actors = implode(',',$actors);
                                    }
                                    $updateArr['actors'] = $actors;
                                }
                                break;
                            case 'myear':
                                if ((empty($v) || preg_match('/^\d{4}$/', $v) === 0) && isset($douban_data['html']['year'])) {
                                    $updateArr['myear'] = $douban_data['html']['year'];
                                }
                                break;
                            case 'lan_guage':
                                if (empty($v) && isset($douban_data['html']['language'])) {
                                    $updateArr['lan_guage'] = $douban_data['html']['language'];
                                }
                                break;
                            case 'types':
                                if (empty($v) && isset($douban_data['html']['types'])) {
                                    $updateArr['types'] = $douban_data['html']['types'];
                                }
                                break;
                            case 'episode_nums':
                                if (empty($v) && isset($douban_data['html']['episode_nums'])) {
                                    $updateArr['episode_nums'] = intval($douban_data['html']['episode_nums']);
                                }
                                break;
                        }
                    }
                    print_r($updateArr);
                    if(!empty($updateArr)) {
                        //保存到数据库
                        $rest = DB::connection($this->dbName)->table($this->tableName)->where('id',$row->id)->update(array_merge($updateArr,['is_douban'=>0]));
                        if ($rest) {
                            $this->info('perfect content update success');
                        } else {
                            $this->error('perfect content update fail');
                        }
                    }
                }
            } while ($tot > 0);
            $this->info('perfect douban content update end!');
            $this->aid = 0;
        }catch (\ErrorException $e){
            $this->error('douban prefect content error exception '.$e->getMessage());
//            $this->call('caiji:movieygdy8',['aid'=>$this->aid]);
            DB::connection($this->dbName)->table($this->tableName)->where('id', $this->aid)->delete();
            $this->perfectContent($this->aid);
        }catch (\Exception $e){
            $this->error('douban prefect content exception '.$e->getMessage());
//            $this->call('caiji:movieygdy8',['aid'=>$this->aid]);
            DB::connection($this->dbName)->table($this->tableName)->where('id', $this->aid)->delete();
            $this->perfectContent($this->aid);
        }
    }

}






