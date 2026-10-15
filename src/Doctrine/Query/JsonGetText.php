<?php

namespace App\Doctrine\Query;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * JSON_GET_TEXT(q.jsonField, :key) is PostgreSQL's "q.json_field ->> :key".
 */
final class JsonGetText extends FunctionNode
{
    private PathExpression $json;
    private Node $key;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return \sprintf(
            '(%s ->> %s)',
            $sqlWalker->walkPathExpression($this->json),
            $sqlWalker->walkStringPrimary($this->key),
        );
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->json = $parser->StateFieldPathExpression();

        $parser->match(TokenType::T_COMMA);

        $this->key = $parser->StringPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
