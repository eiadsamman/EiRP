const std = {
	isdigit: function (val) {
		return '0123456789'.includes(val);
	}
}
const Type = Object.freeze({
	Unknown: 'unknow',
	Literal_Numeric: 'numeric',
	Operator: 'operator',
	Parenthesis_Open: 'par_open',
	Parenthesis_Close: 'par_close',

});

class sOperator {
	constructor(a, b) {
		this.precedence = a;
		this.arguments = b;
	}
};

class sSymbol {
	constructor(a, b = Type.Unknown, c) {
		this.symbol = a;
		this.type = b;
		this.op = c;
	}
	v() {
		return this.symbol + " \t " + this.type + " \t " + (this.op == null ? "" : "(#" + this.op.arguments + ", ^" + this.op.precedence + ")");
	}
}


class ShuntingYard {

	mapOps = Object();
	sExpression;
	stkHolding;
	stkOutput;
	stkSolve;
	symPrevious;
	safe = 0;

	constructor() {
		this.mapOps['/'] = new sOperator(4, 2);
		this.mapOps['*'] = new sOperator(3, 2);
		this.mapOps['+'] = new sOperator(2, 2);
		this.mapOps['-'] = new sOperator(1, 2);

	}
	logStkOutput() {
		for (const s of this.stkOutput) {
			console.log("\t\t%c" + s.symbol + "\t" + s.type + (s.op == null ? "" : "\t(#" + s.op.arguments + ", ^" + s.op.precedence + ")"), 'color:#cfb;line-height:1.6em')
		}
	}

	calc(expression) {
		this.stkHolding = Array();
		this.stkOutput = Array();
		this.symPrevious = new sSymbol("0", Type.Literal_Numeric, 0);
		this.sExpression = expression;
		let pass = 0;
		console.log("%c" + expression, "color: lime;font-size:1.5em");
		for (const c of this.sExpression) {
			//console.log("%c\t" + c, ' color: #ee0;font-weight:bold;')
			if (std.isdigit(c)) {
				this.stkOutput.push(new sSymbol(c, Type.Literal_Numeric, null));
				this.symPrevious = this.stkOutput.at(-1);
				//console.log("\t\t" + this.symPrevious.v())

			} else if (c == '(') {
				this.stkHolding.unshift(new sSymbol(c, Type.Parenthesis_Open));
				this.symPrevious = this.stkHolding[0];
			} else if (c == ')') {
				while (this.stkHolding.length > 0 && this.stkHolding[0].type != Type.Parenthesis_Open) {
					this.stkOutput.push(this.stkHolding[0]);
					this.stkHolding.shift();
				}
				if (this.stkHolding.length == 0) {
					console.log(`!!!!     ERROR! Unexpected parenthesis '${c}'`);
					return false;
				}
				if (this.stkHolding.length > 0 && this.stkHolding[0].type == Type.Parenthesis_Open) {
					this.stkHolding.shift();
				}

				this.symPrevious = new sSymbol(c, Type.Parenthesis_Close, 0);
			} else if (this.mapOps.hasOwnProperty(c)) {
				const new_op = new sOperator(this.mapOps[c].precedence, this.mapOps[c].arguments);

				if (c == '-' || c == '+') {
					if ((this.symPrevious.type != Type.Literal_Numeric && this.symPrevious.type != Type.Parenthesis_Close) || pass == 0) {
						new_op.arguments = 1;
						new_op.precedence = 100;
					}
				}

				while (this.stkHolding.length > 0 && this.stkHolding[0].type != Type.Parenthesis_Open) {
					if (this.stkHolding[0].type == Type.Operator) {
						const holding_stack_op = this.stkHolding[0].op;
						if (holding_stack_op.precedence >= new_op.precedence) {
							this.stkOutput.push(this.stkHolding.shift());
						} else {
							break;
						}
					}
				}
				this.stkHolding.unshift(new sSymbol(c, Type.Operator, new_op));
				this.symPrevious = this.stkHolding[0];
				//console.log("\t\t" + this.symPrevious.v())
			} else {
				throw new Error(`Bad Symbol: '${c}'`);
			}

			pass++;
		}

		while (this.stkHolding.length > 0) {
			this.stkOutput.push(this.stkHolding.shift());
		}

		console.log("Output Stack:")
		this.logStkOutput();


		this.stkSolve = Array();

		for (const inst of this.stkOutput) {
			switch (inst.type) {
				case Type.Literal_Numeric:
					this.stkSolve.push(parseFloat(inst.symbol));
					break;
				case Type.Operator:
					let mem = Array(inst.op.arguments);
					for (let a = 0; a < inst.op.arguments; a++) {
						if (this.stkSolve.length == 0) {
							throw new Error(`Bad Expression`);
						} else {
							mem[a] = this.stkSolve.at(-1);
							this.stkSolve.pop();
						}
					}
					let result = 0.0;
					if (inst.op.arguments == 2) {

						console.log(" -> " + mem[1] + " " + inst.symbol + " " + mem[0]);
						if (inst.symbol == '/') result = mem[1] / mem[0];
						if (inst.symbol == '*') result = mem[1] * mem[0];
						if (inst.symbol == '+') result = mem[1] + mem[0];
						if (inst.symbol == '-') result = mem[1] - mem[0];
					}

					if (inst.op.arguments == 1) {
						if (inst.symbol == '+') result = +mem[0];
						if (inst.symbol == '-') result = -mem[0];
					}

					this.stkSolve.push(result)
					break;
			}
		}
		console.log("Result: %c" + this.stkSolve[0], "color:#7af;");
	}
}


const sy = new ShuntingYard();
sy.calc("1+1-1");
sy.calc("1-1+1");