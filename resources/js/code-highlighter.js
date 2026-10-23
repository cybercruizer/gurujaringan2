const keywordSets = {
    bash: 'case do done elif else esac fi for function if in select then time until while',
    c: 'auto break case char const continue default do double else enum extern float for goto if int long register return short signed sizeof static struct switch typedef union unsigned void volatile while',
    cpp: 'alignas alignof auto bool break case char class const constexpr continue default delete do double else enum explicit extern false float for friend goto if inline int long namespace new nullptr operator private protected public register return short signed sizeof static struct switch template this throw true try typedef typename union unsigned using virtual void volatile while',
    csharp: 'abstract as base bool break byte case catch char checked class const continue decimal default delegate do double else enum event explicit extern false finally fixed float for foreach goto if implicit in int interface internal is lock long namespace new null object operator out override params private protected public readonly ref return sbyte sealed short sizeof stackalloc static string struct switch this throw true try typeof uint ulong unchecked unsafe ushort using virtual void volatile while',
    css: 'important',
    java: 'abstract assert boolean break byte case catch char class const continue default do double else enum extends final finally float for if implements import instanceof int interface long native new package private protected public return short static strictfp super switch synchronized this throw throws transient try void volatile while',
    javascript: 'async await break case catch class const continue debugger default delete do else export extends false finally for from function get if import in instanceof let new null of return set static super switch this throw true try typeof undefined var void while with yield',
    markup: '',
    php: 'abstract and array as break case catch class clone const continue declare default die do else elseif empty endfor endforeach endif endswitch endwhile eval exit extends final finally fn for foreach function global goto if implements include include_once instanceof insteadof interface list match namespace new or print private protected public readonly require require_once return static switch throw trait try unset use var while xor',
    python: 'and as assert async await break class continue def del elif else except false finally for from global if import in is lambda nonlocal not or pass raise return true try while with yield',
    ruby: 'alias and begin break case class def defined do else elsif end ensure false for if in module next nil not or redo rescue retry return self super then true undef unless until when while yield',
};

const bashBuiltins = new Set([
    'awk', 'cat', 'cd', 'chmod', 'chown', 'cp', 'curl', 'echo', 'export', 'find', 'grep', 'head', 'install',
    'kill', 'less', 'ln', 'ls', 'mkdir', 'mv', 'printf', 'pwd', 'rm', 'sed', 'sleep', 'sort', 'sudo', 'tail',
    'tar', 'touch', 'wget', 'xargs',
]);

const escapePattern = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const createPattern = (language) => {
    const keywords = (keywordSets[language] ?? '')
        .split(' ')
        .filter(Boolean)
        .map(escapePattern)
        .join('|');
    const keywordPattern = keywords ? `\\b(?:${keywords})\\b` : '(?!)';
    const commentPattern = language === 'bash' || language === 'python' || language === 'ruby'
        ? '#[^\\n]*'
        : '(?!)';
    const bashCommandPattern = language === 'bash'
        ? `\\b(?:${[...bashBuiltins].map(escapePattern).join('|')})\\b`
        : '(?!)';

    return new RegExp([
        `(?<comment>${commentPattern}|\\/\\/[^\\n]*|\\/\\*.*?\\*\\/)`,
        '(?<string>"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'|`(?:\\\\.|[^`\\\\])*`)',
        '(?<variable>\\$\\{[^}]*\\}|\\$[A-Za-z_][\\w]*|\\$[?#@*!$\\d])',
        `(?<keyword>${keywordPattern})`,
        `(?<builtin>${bashCommandPattern})`,
        '(?<function>\\b[A-Za-z_$][\\w$]*(?=\\s*\\())',
        '(?<number>\\b\\d+(?:\\.\\d+)?\\b)',
        '(?<punctuation>[{}()[\\];,.])',
        '(?<operator>===|!==|=>|==|!=|<=|>=|&&|\\|\\||\\+\\+|--|\\+=|-=|\\*=|\\/=|[-+*/%=<>!&|^~?:])',
    ].join('|'), 'g');
};

const tokenizeLine = (line, pattern) => {
    const fragment = document.createDocumentFragment();
    let offset = 0;
    let match;

    pattern.lastIndex = 0;

    while ((match = pattern.exec(line)) !== null) {
        if (match.index > offset) {
            fragment.append(document.createTextNode(line.slice(offset, match.index)));
        }

        const tokenType = Object.keys(match.groups).find((key) => match.groups[key] !== undefined);
        const token = document.createElement('span');
        token.className = `token ${tokenType}`;
        token.textContent = match[0];
        fragment.append(token);
        offset = pattern.lastIndex;
    }

    if (offset < line.length) {
        fragment.append(document.createTextNode(line.slice(offset)));
    }

    return fragment;
};

const highlightCode = (pre) => {
    const code = pre.querySelector('code') ?? pre;
    const language = [...pre.classList]
        .find((className) => className.startsWith('language-'))
        ?.slice('language-'.length);

    if (!language || !Object.hasOwn(keywordSets, language) || code.dataset.highlighted === 'true') {
        return;
    }

    const pattern = createPattern(language);
    const lines = code.textContent.replace(/\r\n?/g, '\n').split('\n');
    const fragment = document.createDocumentFragment();

    lines.forEach((line) => {
        const lineElement = document.createElement('span');
        lineElement.className = 'code-line';

        if (line.length > 0) {
            lineElement.append(tokenizeLine(line, pattern));
        }

        fragment.append(lineElement);
    });

    code.replaceChildren(fragment);
    code.dataset.highlighted = 'true';
};

export const highlightCodeSamples = (root = document) => {
    root.querySelectorAll('.rich-content pre[class*="language-"]').forEach(highlightCode);
};
