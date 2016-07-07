<?php
/*
 * Xapian library
 *
 * This library file contains easy access functions to use Xapian
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Johan Geuze, Sven Oostenbrink <support@ingiga.com>
 */

/*
 * Autoload the xapian PHP library
 */
include_once('xapian.php');



/*
 * Create an index
 *
 * $xapian = new xapian_index();
 * $xapian->create();
 * $xapian->add('test','1');
 * $xapian->add('test 2','2');
 * $xapian->add('test 4','3');
 * $xapian->add('test 5','4');
 * $xapian->install('companies');
 *
 * search in index
 * $xapian = new xapian_index();
 * $results=$xapian->query($_POST['q'],'companies',0,10);
*/
class xapianbase {
    private $document  = null;
    private $indexer   = null;
    private $query     = null;
    private $enquire   = null;
    private $language  = '';
    private $tempdir   = '';
    public  $target    = '';
    private $name      = '';



    /*
     * Initialize
     */
    function __construct($params = '', $language = null){
        global $_CONFIG;

        array_params($params, 'name');
        array_default($params, 'language', not_empty($language, LANGUAGE));
        array_default($params, 'name'    , 'default');

        if(empty($_CONFIG['language']['supported'][$params['language']])){
            throw new bException('xapianbase->__construct(): Specified language "'.str_log($params['language']).'" is not supported');
        }

        $this->language = strtolower($_CONFIG['language']['supported'][$params['language']]);
        $this->target   = slash($_CONFIG['xapian']['dir']).$params['name'].'/';
        $this->name     = $params['name'];

        load_libs('json');
    }



    /*
     * Create a temporary database
     */
    function create($params = null) {
        global $_CONFIG;

        try {
            /*
             * Create database in temp dir
             */
            load_libs('file');

            $this->tempdir  = file_temp_dir('xapian/'.$params['name'].'/', null, 0770);
            $this->database = new XapianWritableDatabase($this->tempdir, Xapian::DB_CREATE_OR_OPEN);
            $this->indexer  = new XapianTermGenerator();

            $stemmer = new XapianStem($this->language);
            $this->indexer->set_stemmer($stemmer);

            //need to chmod the files
            file_chmod_tree($this->tempdir, 0660, 0770);

        }catch(Exception $e){
            throw new bException('xapianbase->create(): Failed to create temporary index : ', $e);
        }
    }



    /*
     * Add a new document to index
     */
    function add($document, $key) {
        try {
            if(!is_string($document)){
                /*
                 * Automatically convert to UTF8 string
                 */
                $document = json_encode_custom($document);
            }

            $doc = new XapianDocument();
            $doc->set_data($document);
            $doc->add_term($key);

            $this->indexer->set_document($doc);
            $this->indexer->index_text($document);

            $this->database->replace_document($key, $doc);

        }catch(Exception $e){
            throw new bException('xapianbase->add(): failed to add document to index : '.$e);
        }
    }



    /*
     * Install (and overwrite) index to target directory
     */
    function install() {
        global $_CONFIG;

        try {
            $this->database = $this->indexer = null;

            if(!file_exists($_CONFIG['xapian']['dir'])) {
                mkdir($_CONFIG['xapian']['dir']);
            }

            /*
             * If target already exists, delete it first
             */
            if(file_exists($this->target)){
                load_libs('file');
                file_delete_tree($this->target);
            }

            /*
             * Move to targetdir
             */
            safe_exec('mv '.$this->tempdir.' '.$this->target);

        }catch(Exception $e){
            throw new bException('xapianbase->install(): Failed to install xapian index to '.str_log($this->tempdir).' : ', $e);
        }
    }



    /*
     * Execute specified query
     */
    function query($query, $start = 0, $rows = 10, $partial = true) {
        global $_CONFIG;

        try {
            load_libs('json');

            $database   = new XapianDatabase($this->target);

            // Start an enquire session.
            $enquire    = new XapianEnquire($database);
            $qp         = new XapianQueryParser();
            $stemmer    = new XapianStem($this->language);

            $qp->set_stemmer($stemmer);
            $qp->set_database($database);
            $qp->set_stemming_strategy(XapianQueryParser::STEM_SOME);
            //$query=preg_replace('/\s+/', ' ',$query);
            $query = $qp->parse_query(strtolower($query), ($partial ? XapianQueryParser::FLAG_PARTIAL : 0));

            // Find results for the query.
            $enquire->set_query($query);

            // Display the results.
            $matches       = $enquire->get_mset($start, $rows);
            $total_results = $matches->get_matches_estimated();

            $data = array();
            $i    = $matches->begin();

            while (!$i->equals($matches->end())) {
                $n = $i->get_rank() + 1;

                $data[$n] = array('percent' => $i->get_percent(),
                                  'data'    => json_decode_custom($i->get_document()->get_data()));
                $i->next();
            }

            return array('results' => $data,
                         'count'   => $total_results);

        }catch(Exception $e){
            throw new bException('xapianbase->query: Failed to query "'.str_log($this->target).'" with query "'.str_log($query).'": ', $e);
        }
    }
}
?>
