<?php
/**
 * Plural Maker — Trongate v2 child module within trongate_control.
 *
 * A comprehensive English pluralisation engine using pattern-matching rules
 * and compact embedded dictionaries. No database dependency.
 *
 * Loading from a sibling module (e.g., Evo):
 *   $this->module('trongate_control-plural_maker');
 *   $plural = $this->plural_maker->make_plural('Tooth');
 *   // Returns: 'Teeth'
 *
 * @package Trongate
 * @author  Grady 🎩
 */
class Plural_maker extends Trongate {

    // ============================================
    // Dictionaries
    // ============================================

    /**
     * Unchanging / uncountable nouns (singular === plural).
     */
    private array $unchanging = [
        'abdomen', 'adulthood', 'advice', 'agenda', 'aid', 'alcohol', 'anime',
        'blood', 'bronchitis', 'butter', 'candelabra', 'canvas', 'carp', 'cash',
        'chalice', 'chaos', 'charles', 'chess', 'clothing', 'cod', 'commerce',
        'contact lens', 'criteria', 'cyclamen', 'data', 'deer', 'digestion',
        'dolman', 'elk', 'energy', 'equipment', 'fish', 'fun', 'garbage',
        'goldfish', 'grouse', 'health', 'homework', 'hovercraft',
        'housework', 'information', 'james', 'jellyfish', 'justice', 'kingfish',
        'labour', 'laryngitis', 'lens', 'literature', 'machinery',
        'mail', 'mathematics', 'measles', 'media', 'music', 'news', 'pancreas',
        'people', 'physics', 'pike', 'police', 'politics', 'pollution', 'rain',
        'reindeer', 'research', 'rhinoceros', 'rice', 'salmon', 'scissors',
        'series', 'sheep', 'shrimp', 'slice', 'squid', 'staff',
        'stamen', 'subconscious', 'swordfish', 'tennis', 'thomas', 'traffic',
        'trellis', 'trout', 'tuna', 'wealth', 'welfare', 'whereas',
        'wildlife', 'zebrafish', 'blackfish', 'crayfish', 'hair',
    ];

    /**
     * Words that are already plural and end in 's'.
     */
    private array $already_plural_s = [
        'acoustics', 'athletics', 'barracks', 'bellows', 'bifocals',
        'bloomers', 'boots', 'briefs', 'castanets', 'children', 'clogs',
        'clothes', 'crackers', 'cuff-links', 'cymbals', 'dishes',
        'dungarees', 'earmuffs', 'earrings', 'economics', 'ethics',
        'eyebrows', 'eyeglasses', 'eyelashes', 'eyelids', 'facilities',
        'fairies', 'fatigues', 'feet', 'flip-flops', 'gaiters', 'galoshes',
        'geese', 'genetics', 'glasses', 'gloves', 'goggles', 'gumshoes',
        'gymnastics', 'hobbies', 'hops', 'hydraulics', 'in-laws',
        'intestines', 'jeans', 'jodhpurs', 'kidneys', 'klomps', 'knickers',
        'linguistics', 'maracas', 'men', 'mice', 'moccasins', 'others',
        'oxen', 'pajamas', 'panties', 'pants', 'parentheses', 'cherries',
        'sandals', 'savings', 'shears', 'shoes', 'shorts', 'sideburns',
        'slippers', 'sneakers', 'socks', 'spectacles', 'steps', 'stockings',
        'sunglasses', 'suspenders', 'sweats', 'sweets', 'teeth', 'thanks',
        'thermals', 'thongs', 'tights', 'togs', 'trousers', 'underclothes',
        'underpants', 'upstairs', 'women', 'yours',
        'yourselves', 'ourselves', 'themselves',
    ];

    /**
     * Irregular plurals.
     */
    private array $irregular = [
        'child' => 'children', 'person' => 'people', 'man' => 'men',
        'woman' => 'women', 'mouse' => 'mice', 'foot' => 'feet',
        'tooth' => 'teeth', 'goose' => 'geese', 'ox' => 'oxen',
        'louse' => 'lice', 'die' => 'dice',
        'chairperson' => 'chairpeople', 'congressperson' => 'congresspeople',
        'councilperson' => 'councilpeople', 'ex-wife' => 'ex-wives',
        'midwife' => 'midwives', 'worklife' => 'worklives',
        'cactus' => 'cacti', 'focus' => 'foci', 'octopus' => 'octopi',
        'virus' => 'viri', 'index' => 'indices', 'appendix' => 'appendices',
        'whiskey' => 'whiskies',
        'blouse' => 'blice', 'money' => 'monies', 'golf' => 'golves',
        'dolman' => 'dolmen', 'pince-nez' => 'pince-nezs', 'quartz' => 'quartzs',
        'who' => 'whos',
        'this' => 'these', 'that' => 'those',
        'he' => 'they', 'she' => 'they', 'me' => 'us',
        'we' => 'we', 'they' => 'they', 'you' => 'you', 'us' => 'us',
        'them' => 'them',
        'myself' => 'ourselves', 'himself' => 'themselves',
        'herself' => 'themselves', 'itself' => 'themselves',
        'yourself' => 'yourselves',
        'hers' => 'hers', 'his' => 'his', 'its' => 'its',
        'ours' => 'ours', 'theirs' => 'theirs',
    ];

    /**
     * Compound nouns ending in -man that take -men.
     */
    private array $man_to_men = [
        'bondsman', 'boogeyman', 'chairman', 'congressman', 'councilman',
        'craftsman', 'fireman', 'fisherman', 'lumberman', 'mailman',
        'marksman', 'middleman', 'plowman', 'policeman', 'salesman',
        'snowman', 'wingman', 'sportsman', 'workman',
    ];

    /**
     * Words ending in -o that take -s rather than -oes.
     */
    private array $o_adds_s = [
        'alto', 'armadillo', 'banjo', 'bolero', 'bongo', 'bronco',
        'calico', 'cappuccino', 'cargo', 'casino', 'cello', 'chino',
        'congo', 'disco', 'dynamo', 'fresco', 'gazebo', 'go', 'gyro',
        'hello', 'helo', 'judo', 'jumbo', 'kendo', 'kimono',
        'limo', 'maestro', 'mambo', 'metro', 'mosquito', 'octavo',
        'photo', 'piano', 'piccolo', 'pinto', 'polo', 'silo',
        'sombrero', 'soprano', 'stiletto', 'tempo', 'torso',
        'tuxedo', 'vertigo', 'two', 'poncho',
    ];

    /**
     * Latin -um → -a nouns.
     */
    private array $um_to_a = [
        'datum' => 'data', 'millennium' => 'millennia', 'medium' => 'media',
        'bacterium' => 'bacteria', 'curriculum' => 'curricula',
        'memorandum' => 'memoranda', 'stratum' => 'strata',
    ];

    /**
     * Latin/Greek -on → -a nouns.
     */
    private array $on_to_a = [
        'automaton' => 'automata', 'criterion' => 'criteria',
        'phenomenon' => 'phenomena',
    ];

    /**
     * f/fe → ves mappings.
     */
    private array $f_to_ves = [
        'leaf' => 'leaves', 'life' => 'lives', 'wife' => 'wives',
        'knife' => 'knives', 'wolf' => 'wolves', 'shelf' => 'shelves',
        'half' => 'halves', 'calf' => 'calves', 'elf' => 'elves',
        'loaf' => 'loaves', 'thief' => 'thieves', 'self' => 'selves',
        'hoof' => 'hooves', 'scarf' => 'scarves', 'wharf' => 'wharves',
    ];

    // ============================================
    // Public API
    // ============================================

    /**
     * Build the reverse irregular lookup on first use.
     */
    private ?array $plural_to_singular = null;
    private ?array $ves_to_f = null;
    private ?array $a_to_um = null;
    private ?array $a_to_on = null;

    /**
     * Convert a singular word (or phrase) to its plural form.
     *
     * Handles multi-word names by pluralising only the last word.
     * Preserves the capitalisation pattern of the input.
     *
     * @param  string $word  The singular word or phrase.
     * @return string        The plural form.
     */
    public function make_plural(string $word): string {
        $word = trim($word);

        // Handle multi-word: pluralise only the LAST word
        $parts = explode(' ', $word);
        if (count($parts) > 1) {
            $full_lower = strtolower($word);
            // Check if the full phrase is unchanging
            if (in_array($full_lower, $this->unchanging, true)) {
                return $word;
            }
            $last = array_pop($parts);
            $parts[] = $this->pluralize_single($last);
            return implode(' ', $parts);
        }

        return $this->pluralize_single($word);
    }

    /**
     * Convert a plural word (or phrase) back to its singular form.
     *
     * Handles multi-word names by singularising only the last word.
     * Preserves the capitalisation pattern of the input.
     *
     * @param  string $word  The plural word or phrase.
     * @return string        The singular form.
     */
    public function get_singular(string $word): string {
        $word = trim($word);

        // Build reverse lookups on first call
        if ($this->plural_to_singular === null) {
            $this->plural_to_singular = array_flip($this->irregular);
            $this->ves_to_f = array_flip($this->f_to_ves);
            $this->a_to_um = array_flip($this->um_to_a);
            $this->a_to_on = array_flip($this->on_to_a);
        }

        // Handle multi-word: singularise only the LAST word
        $parts = explode(' ', $word);
        if (count($parts) > 1) {
            $full_lower = strtolower($word);
            // Check if the full phrase is unchanging
            if (in_array($full_lower, $this->unchanging, true)) {
                return $word;
            }
            $last = array_pop($parts);
            $parts[] = $this->singularize_single($last);
            return implode(' ', $parts);
        }

        return $this->singularize_single($word);
    }

    // ============================================
    // Private Engine
    // ============================================

    /**
     * Pluralise a single word.
     */
    private function pluralize_single(string $word): string {
        $lower = strtolower($word);

        // 0. Already plural
        if (in_array($lower, $this->already_plural_s, true)) {
            return $word;
        }

        // 1. Unchanging
        if (in_array($lower, $this->unchanging, true)) {
            return $word;
        }

        // 2. Irregulars
        if (isset($this->irregular[$lower])) {
            return $this->preserve_case($word, $this->irregular[$lower]);
        }

        // 3. -sis → -ses
        if (preg_match('/sis$/i', $word)) {
            return substr($word, 0, -2) . 'es';
        }

        // 4. -man → -men
        if (preg_match('/man$/i', $word) && in_array($lower, $this->man_to_men, true)) {
            return substr($word, 0, -3) . 'men';
        }

        // 5. -um → -a
        if (preg_match('/um$/i', $word) && isset($this->um_to_a[$lower])) {
            return $this->preserve_case($word, $this->um_to_a[$lower]);
        }

        // 6. -on → -a
        if (preg_match('/on$/i', $word) && isset($this->on_to_a[$lower])) {
            return $this->preserve_case($word, $this->on_to_a[$lower]);
        }

        // 7. f/fe → ves
        if (preg_match('/fe?$/i', $word)) {
            foreach ($this->f_to_ves as $singular => $plural) {
                if ($lower === $singular || $lower === $singular . 'e') {
                    return $this->preserve_case($word, $plural);
                }
            }
        }

        // 8. s, x, z, ch, sh → es
        if (preg_match('/(s|x|z|ch|sh)$/i', $word)) {
            $result = $this->preserve_case($word, $lower . 'es');
            return $result;
        }

        // 9. consonant + y → ies
        if (preg_match('/[bcdfghjklmnpqrstvwxz]y$/i', $word)) {
            $result = $this->preserve_case($word, substr($lower, 0, -1) . 'ies');
            return $result;
        }

        // 10. -o ending
        if (preg_match('/o$/i', $word)) {
            if (in_array($lower, $this->o_adds_s, true)) {
                $result = $word . 's';
                return $result;
            }
            if (preg_match('/[bcdfghjklmnpqrstvwxz]o$/i', $word)) {
                $result = $word . 'es';
                return $result;
            }
            $result = $word . 's';
            return $result;
        }

        // 11. Default: add 's'
        $result = $word . 's';
        return $result;
    }

    /**
     * Derive the singular form of a single plural word.
     */
    private function singularize_single(string $word): string {
        $lower = strtolower($word);

        // 0. Check ALL explicit dictionary reverse lookups before unchanging.
        //    Some words (people, data, criteria) exist in both the unchanging
        //    list AND a reverse mapping — the explicit reverse wins.

        // 0a. Reverse irregular lookup
        if (isset($this->plural_to_singular[$lower])) {
            return $this->preserve_case($word, $this->plural_to_singular[$lower]);
        }

        // 0b. Reverse -ves → -f/-fe (known dictionary mappings)
        if (preg_match('/ves$/i', $word) && isset($this->ves_to_f[$lower])) {
            return $this->preserve_case($word, $this->ves_to_f[$lower]);
        }

        // 0c. Reverse -a → -um (Latin)
        if (preg_match('/a$/i', $word) && isset($this->a_to_um[$lower])) {
            return $this->preserve_case($word, $this->a_to_um[$lower]);
        }

        // 0d. Reverse -a → -on (Greek)
        if (preg_match('/a$/i', $word) && isset($this->a_to_on[$lower])) {
            return $this->preserve_case($word, $this->a_to_on[$lower]);
        }

        // 1. Unchanging (singular === plural)
        if (in_array($lower, $this->unchanging, true)) {
            return $word;
        }

        // 2. Reverse -men → -man (for known compounds)
        if (preg_match('/men$/i', $word)) {
            $candidate = substr($word, 0, -3) . 'man';
            $candidate_lower = strtolower($candidate);
            if (in_array($candidate_lower, $this->man_to_men, true)) {
                return $candidate;
            }
            // Also try as a general -man compound
            $candidate_no_man = substr($word, 0, -3);
            if (strlen($candidate_no_man) > 0) {
                return $candidate;
            }
        }

        // 3. Reverse -ses → -sis
        if (preg_match('/ses$/i', $word) && strlen($word) > 3) {
            $candidate = substr($word, 0, -3) . 'sis';
            return $candidate;
        }

        // 4. Reverse -ices → -ex/-ix
        if (preg_match('/ices$/i', $word)) {
            // Try -ex ending: index → indices
            $candidate = substr($word, 0, -4) . 'ex';
            // Try -ix ending: appendix → appendices
            $candidate2 = substr($word, 0, -4) . 'ix';
            $lower_candidate = strtolower($candidate);
            $lower_candidate2 = strtolower($candidate2);
            // Prefer whichever exists in irregulars
            if (isset($this->irregular[$lower_candidate])) {
                return $candidate;
            }
            if (isset($this->irregular[$lower_candidate2])) {
                return $candidate2;
            }
        }

        // 5. Reverse -ies → -y (consonant + y)
        if (preg_match('/ies$/i', $word) && strlen($word) > 3) {
            $candidate = substr($word, 0, -3) . 'y';
            return $candidate;
        }

        // 6. Reverse -ves → -f/-fe (general pattern)
        if (preg_match('/ves$/i', $word) && strlen($word) > 3) {
            $candidate_f = substr($word, 0, -3) . 'f';
            return $candidate_f;
        }

        // 7. Reverse -oes → -o (for consonant+o words)
        if (preg_match('/oes$/i', $word) && strlen($word) > 3) {
            $candidate = substr($word, 0, -3) . 'o';
            $candidate_lower = strtolower($candidate);
            if (!in_array($candidate_lower, $this->o_adds_s, true)) {
                return $candidate;
            }
        }

        // 8. Reverse -es → -∅ (for s, x, z, ch, sh words)
        if (preg_match('/es$/i', $word) && strlen($word) > 2) {
            $candidate = substr($word, 0, -2);
            return $candidate;
        }

        // 9. Reverse -s → -∅ (default regular plural)
        if (preg_match('/s$/i', $word) && strlen($word) > 1) {
            $candidate = substr($word, 0, -1);
            return $candidate;
        }

        // 10. Default: return word as-is
        return $word;
    }

    /**
     * Preserve the capitalisation pattern of the original word.
     *
     * @param  string $original      The input word.
     * @param  string $transformed   The transformed word (lowercase).
     * @return string                The transformed word with matched case.
     */
    private function preserve_case(string $original, string $transformed): string {
        if (ctype_upper($original)) {
            return strtoupper($transformed);
        }
        if (ctype_upper($original[0])) {
            return ucfirst($transformed);
        }
        return $transformed;
    }

}
